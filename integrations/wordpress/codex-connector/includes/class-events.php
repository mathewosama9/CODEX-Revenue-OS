<?php
/**
 * Plugin → CODEX events (protocol §3), delivered through Action Scheduler.
 *
 * Queued args never contain PII: cart.updated carries only the cart id and the
 * Cart is rebuilt from the table at send time (so a withdrawn consent is honoured).
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Events {

	/** Backoff after attempt n (seconds): 1m, 5m, 30m, 2h, 6h. */
	const BACKOFF = array( 60, 300, 1800, 7200, 21600 );

	const MAX_BODY = 1048576;

	/** @var array<string,bool> Events queued during this request (de-dupe). */
	private static $queued = array();

	public static function init() {
		add_action( CODEX_CONNECTOR_SEND_HOOK, array( __CLASS__, 'process' ), 10, 1 );

		// Orders.
		add_action( 'woocommerce_new_order', array( __CLASS__, 'on_new_order' ), 20, 1 );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_order_status_changed' ), 20, 1 );
		// A partial refund leaves the status alone, so status_changed alone would miss it.
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'on_order_status_changed' ), 20, 1 );

		// Products.
		add_action( 'woocommerce_new_product', array( __CLASS__, 'on_product_saved' ), 20, 1 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'on_product_saved' ), 20, 1 );
		add_action( 'woocommerce_new_product_variation', array( __CLASS__, 'on_variation_saved' ), 20, 1 );
		add_action( 'woocommerce_update_product_variation', array( __CLASS__, 'on_variation_saved' ), 20, 1 );
		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'on_stock_changed' ), 20, 1 );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'on_stock_changed' ), 20, 1 );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'on_product_saved' ), 20, 1 );
		add_action( 'trashed_post', array( __CLASS__, 'on_post_trashed' ), 20, 1 );
		add_action( 'untrashed_post', array( __CLASS__, 'on_post_untrashed' ), 20, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_post_trashed' ), 20, 1 );
	}

	/* ------------------------------------------------------------------ hooks */

	/**
	 * @param int $order_id Order id.
	 */
	public static function on_new_order( $order_id ) {
		self::queue( 'order.created', array( 'id' => (string) absint( $order_id ) ) );
	}

	/**
	 * @param int $order_id Order id.
	 */
	public static function on_order_status_changed( $order_id ) {
		$key = 'order.created:' . absint( $order_id );
		if ( isset( self::$queued[ $key ] ) ) {
			return; // Just created in this request; CODEX fetches the latest state anyway.
		}
		self::queue( 'order.updated', array( 'id' => (string) absint( $order_id ) ) );
	}

	/**
	 * @param int|WC_Product $product Product or id.
	 */
	public static function on_product_saved( $product ) {
		$id = is_object( $product ) && method_exists( $product, 'get_id' ) ? $product->get_id() : absint( $product );
		if ( is_object( $product ) && method_exists( $product, 'get_parent_id' ) && $product->get_parent_id() ) {
			$id = $product->get_parent_id();
		} elseif ( $id && 'product_variation' === get_post_type( $id ) ) {
			$id = wp_get_post_parent_id( $id );
		}
		if ( $id ) {
			self::queue( 'product.updated', array( 'id' => (string) $id ) );
		}
	}

	/**
	 * @param int $variation_id Variation id.
	 */
	public static function on_variation_saved( $variation_id ) {
		$parent = wp_get_post_parent_id( absint( $variation_id ) );
		if ( $parent ) {
			self::queue( 'product.updated', array( 'id' => (string) $parent ) );
		}
	}

	/**
	 * @param WC_Product $product Product or variation.
	 */
	public static function on_stock_changed( $product ) {
		self::on_product_saved( $product );
	}

	/**
	 * @param int $post_id Post id.
	 */
	public static function on_post_trashed( $post_id ) {
		if ( 'product' === get_post_type( $post_id ) ) {
			self::queue( 'product.deleted', array( 'id' => (string) absint( $post_id ) ) );
		}
	}

	/**
	 * @param int $post_id Post id.
	 */
	public static function on_post_untrashed( $post_id ) {
		if ( 'product' === get_post_type( $post_id ) ) {
			self::queue( 'product.updated', array( 'id' => (string) absint( $post_id ) ) );
		}
	}

	/* ------------------------------------------------------------------ queue */

	/**
	 * Queue an event. $data must not contain PII (cart.updated uses {cart_id}).
	 *
	 * @param string $event Event name.
	 * @param array  $data  Data (or reference).
	 */
	public static function queue( $event, array $data ) {
		if ( ! Codex_Connector_Settings::is_configured() ) {
			return;
		}

		$ref = $event . ':' . ( isset( $data['id'] ) ? $data['id'] : ( isset( $data['cart_id'] ) ? $data['cart_id'] : '' ) );
		if ( 'product.deleted' === $event ) {
			unset( self::$queued[ 'product.updated:' . $data['id'] ] );
		}
		if ( isset( self::$queued[ $ref ] ) ) {
			return;
		}
		self::$queued[ $ref ] = true;

		$args = array(
			array(
				'event' => $event,
				'data'  => $data,
			),
		);

		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			self::process( $args[0] );
			return;
		}

		// Same event for the same object already waiting? Then one send is enough.
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( CODEX_CONNECTOR_SEND_HOOK, $args, CODEX_CONNECTOR_AS_GROUP ) ) {
			return;
		}

		as_enqueue_async_action( CODEX_CONNECTOR_SEND_HOOK, $args, CODEX_CONNECTOR_AS_GROUP );
	}

	/**
	 * Action Scheduler callback. Retries on network errors, 429 and 5xx only.
	 *
	 * @param array $job {event, data, event_id?, occurred_at?, attempt?}.
	 */
	public static function process( $job ) {
		if ( ! is_array( $job ) || empty( $job['event'] ) || ! Codex_Connector_Settings::is_configured() ) {
			return;
		}

		$event   = (string) $job['event'];
		$ref     = isset( $job['data'] ) && is_array( $job['data'] ) ? $job['data'] : array();
		$attempt = isset( $job['attempt'] ) ? (int) $job['attempt'] : 0;

		// Event id and occurred_at are fixed on the first attempt and reused on retries (de-dupe key).
		$event_id    = ! empty( $job['event_id'] ) ? (string) $job['event_id'] : wp_generate_uuid4();
		$occurred_at = ! empty( $job['occurred_at'] ) ? (string) $job['occurred_at'] : Codex_Connector_Plugin::iso( time() );

		$resolved = self::resolve( $event, $ref );
		if ( null === $resolved ) {
			return; // Nothing (allowed) to send any more, e.g. consent withdrawn.
		}
		list( $event, $data ) = $resolved;

		$res = self::post( $event, $data, $event_id, $occurred_at );

		if ( $res['code'] >= 200 && $res['code'] < 300 ) {
			return;
		}

		$retryable = 0 === $res['code'] || 429 === $res['code'] || $res['code'] >= 500;
		if ( ! $retryable || $attempt >= count( self::BACKOFF ) ) {
			Codex_Connector_Plugin::log( 'warning', sprintf( 'Event %s (%s) dropped after attempt %d: HTTP %d.', $event, $event_id, $attempt + 1, $res['code'] ) );
			return;
		}

		$next = array(
			array(
				'event'       => (string) $job['event'],
				'data'        => $ref,
				'event_id'    => $event_id,
				'occurred_at' => $occurred_at,
				'attempt'     => $attempt + 1,
			),
		);
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + self::BACKOFF[ $attempt ], CODEX_CONNECTOR_SEND_HOOK, $next, CODEX_CONNECTOR_AS_GROUP );
		}
		Codex_Connector_Plugin::log( 'info', sprintf( 'Event %s (%s) will retry in %ds (HTTP %d).', $event, $event_id, self::BACKOFF[ $attempt ], $res['code'] ) );
	}

	/**
	 * Turn a queued reference into the final event + data (or null to skip).
	 *
	 * @param string $event Event.
	 * @param array  $ref   Queued data.
	 * @return array|null [event, data]
	 */
	private static function resolve( $event, array $ref ) {
		if ( 'cart.updated' === $event ) {
			$row = isset( $ref['cart_id'] ) ? Codex_Connector_Carts::find( (string) $ref['cart_id'] ) : null;
			if ( ! $row || 1 !== (int) $row->consent || empty( $row->phone ) ) {
				return null; // §6: never send a cart without consent.
			}
			return array( 'cart.updated', Codex_Connector_Carts::to_cart( $row ) );
		}

		if ( 'product.updated' === $event && isset( $ref['id'] ) ) {
			$status = get_post_status( (int) $ref['id'] );
			if ( false === $status || 'trash' === $status ) {
				return array( 'product.deleted', array( 'id' => (string) $ref['id'] ) );
			}
		}

		return array( $event, empty( $ref ) ? new stdClass() : $ref );
	}

	/**
	 * Send immediately (Test connection, deactivation). No retries.
	 *
	 * @param string       $event Event.
	 * @param array|object $data  Data.
	 * @return array{code:int, body:string, error:string}
	 */
	public static function send_now( $event, $data ) {
		return self::post( $event, $data, wp_generate_uuid4(), Codex_Connector_Plugin::iso( time() ) );
	}

	/**
	 * @return array
	 */
	public static function ping_data() {
		return array(
			'woocommerce_version' => defined( 'WC_VERSION' ) ? WC_VERSION : '',
			'checkout'            => Codex_Connector_Plugin::checkout_type(),
			'currency'            => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'price_decimals'      => Codex_Connector_Plugin::price_decimals(),
			'timezone'            => wp_timezone_string(),
		);
	}

	/**
	 * Signed POST to {api}/webhooks/woocommerce/{store_id}.
	 *
	 * @param string       $event       Event.
	 * @param array|object $data        Data.
	 * @param string       $event_id    UUID v4.
	 * @param string       $occurred_at ISO 8601.
	 * @return array{code:int, body:string, error:string}
	 */
	private static function post( $event, $data, $event_id, $occurred_at ) {
		$api    = Codex_Connector_Settings::get( 'api_base' );
		$store  = Codex_Connector_Settings::get( 'store_id' );
		$secret = Codex_Connector_Settings::get( 'secret' );

		$body = wp_json_encode(
			array(
				'event'          => $event,
				'occurred_at'    => $occurred_at,
				'plugin_version' => CODEX_CONNECTOR_VERSION,
				'data'           => $data,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		if ( false === $body || strlen( $body ) > self::MAX_BODY ) {
			Codex_Connector_Plugin::log( 'error', sprintf( 'Event %s (%s) not sent: body could not be encoded or is over 1 MB.', $event, $event_id ) );
			return array(
				'code'  => 413,
				'body'  => '',
				'error' => 'payload too large',
			);
		}

		$timestamp = time();
		$url       = $api . '/webhooks/woocommerce/' . rawurlencode( $store );

		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'headers'     => array(
					'Content-Type'      => 'application/json',
					'Accept'            => 'application/json',
					'X-Codex-Timestamp' => (string) $timestamp,
					'X-Codex-Signature' => Codex_Connector_Signature::sign( $secret, Codex_Connector_Signature::post_string( $timestamp, $body ) ),
					'X-Codex-Event-Id'  => $event_id,
					'User-Agent'        => 'CODEX-Connector/' . CODEX_CONNECTOR_VERSION . '; ' . home_url( '/' ),
				),
				'body'        => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			Codex_Connector_Plugin::log( 'warning', sprintf( 'Event %s (%s) network error: %s', $event, $event_id, $response->get_error_code() ) );
			return array(
				'code'  => 0,
				'body'  => '',
				'error' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		Codex_Connector_Plugin::log( $code >= 200 && $code < 300 ? 'info' : 'warning', sprintf( 'Event %s (%s) -> HTTP %d.', $event, $event_id, $code ) );

		return array(
			'code'  => $code,
			'body'  => (string) wp_remote_retrieve_body( $response ),
			'error' => '',
		);
	}
}
