<?php
/**
 * Checkout consent checkbox + cart capture (protocol §5, §6).
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Cart_Capture {

	const FIELD_CLASSIC = 'codex_whatsapp_consent';
	const FIELD_BLOCKS  = 'codex-connector/whatsapp-consent';
	const AJAX_ACTION   = 'codex_capture';
	const NONCE_ACTION  = 'codex_capture';
	const RATE_LIMIT    = 30;  // Requests…
	const RATE_WINDOW   = 600; // …per 10 minutes per session.

	public static function init() {
		// Classic checkout.
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'render_classic_field' ) );

		// Blocks checkout (Additional Checkout Fields API, WC 8.9+).
		// Registered on init (after our textdomain loads) so the label is translated.
		add_action( 'init', array( __CLASS__, 'register_blocks_field' ), 20 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		// wc-ajax endpoint (works for guests; WC session is loaded).
		add_action( 'wc_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'handle_capture' ) );

		// Order placed from a captured cart: link them. The cart stays open until the order is paid,
		// so a customer who drops off at the payment page can still be reminded.
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_order_placed' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_order_placed' ), 20, 1 );
		// Paid (or cash on delivery, which goes straight to processing) -> cart.purchased.
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'on_order_paid' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'on_order_paid' ), 20, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'on_order_paid' ), 20, 1 );
	}

	/* -------------------------------------------------------------- render */

	/**
	 * Unticked, optional checkbox under the billing form.
	 *
	 * @param WC_Checkout $checkout Checkout.
	 */
	public static function render_classic_field( $checkout ) {
		if ( ! Codex_Connector_Settings::is_configured() ) {
			return;
		}
		woocommerce_form_field(
			self::FIELD_CLASSIC,
			array(
				'type'     => 'checkbox',
				'class'    => array( 'form-row-wide', 'codex-whatsapp-consent' ),
				'label'    => esc_html( Codex_Connector_Settings::consent_text() ),
				'required' => false,
				'default'  => 0,
			),
			0
		);
	}

	public static function register_blocks_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) || ! Codex_Connector_Settings::is_configured() ) {
			return;
		}
		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => self::FIELD_BLOCKS,
					'label'    => Codex_Connector_Settings::consent_text(),
					'location' => 'contact',
					'type'     => 'checkbox',
					'required' => false,
				)
			);
		} catch ( Exception $e ) {
			Codex_Connector_Plugin::log( 'warning', 'Could not register the Blocks consent field: ' . $e->getMessage() );
		}
	}

	public static function enqueue() {
		if ( ! Codex_Connector_Settings::is_configured() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$blocks = 'blocks' === Codex_Connector_Plugin::checkout_type();
		$deps   = $blocks ? array( 'wp-data' ) : array( 'jquery' );

		wp_enqueue_script(
			'codex-connector-checkout',
			CODEX_CONNECTOR_URL . 'assets/checkout.js',
			$deps,
			CODEX_CONNECTOR_VERSION,
			true
		);
		// No secret here: only the endpoint, a nonce and field ids.
		wp_localize_script(
			'codex-connector-checkout',
			'codexConnector',
			array(
				'endpoint'     => WC_AJAX::get_endpoint( self::AJAX_ACTION ),
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
				'classicField' => self::FIELD_CLASSIC,
				'blocksField'  => self::FIELD_BLOCKS,
			)
		);
	}

	/* -------------------------------------------------------------- capture */

	/**
	 * wc-ajax=codex_capture: phone + consent from checkout.js.
	 */
	public static function handle_capture() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'code' => 'bad_nonce' ), 403 );
		}
		if ( ! Codex_Connector_Settings::is_configured() || ! WC()->session || ! WC()->cart ) {
			wp_send_json_error( array( 'code' => 'unavailable' ), 400 );
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
		$session_key = (string) WC()->session->get_customer_id();
		if ( '' === $session_key ) {
			wp_send_json_error( array( 'code' => 'no_session' ), 400 );
		}

		if ( ! self::within_rate_limit( $session_key ) ) {
			wp_send_json_error( array( 'code' => 'rate_limited' ), 429 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$consent   = isset( $_POST['consent'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['consent'] ) );
		$raw_phone = isset( $_POST['phone'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['phone'] ) ), 0, 40 ) : '';
		// phpcs:enable

		$row   = Codex_Connector_Carts::find_by_session( $session_key );
		$phone = self::normalize_phone( $raw_phone );

		// No consent (or invalid phone): send nothing and forget any stored phone.
		if ( ! $consent || null === $phone ) {
			if ( $row && 'open' === $row->status ) {
				Codex_Connector_Carts::update(
					$row->id,
					array(
						'phone'        => null,
						'consent'      => 0,
						'payload_hash' => '',
						'updated_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
			}
			wp_send_json_success( array( 'captured' => false ) );
		}

		$cart = self::snapshot_cart();
		if ( empty( $cart['items'] ) ) {
			wp_send_json_success( array( 'captured' => false ) );
		}

		$text    = Codex_Connector_Settings::consent_text();
		$version = Codex_Connector_Settings::consent_text_version( $text );
		$hash    = sha1( wp_json_encode( array( $cart['items'], $cart['total'], $cart['currency'], $phone, $text ) ) );
		$now     = gmdate( 'Y-m-d H:i:s' );
		$user_id = get_current_user_id();

		$fields = array(
			'status'               => 'open',
			'currency'             => $cart['currency'],
			'total'                => $cart['total'],
			'items'                => wp_json_encode( $cart['items'], JSON_UNESCAPED_UNICODE ),
			'phone'                => $phone,
			'consent'              => 1,
			'consent_text'         => $text,
			'consent_text_version' => $version,
			'customer_id'          => $user_id ? $user_id : null,
			'payload_hash'         => $hash,
			'updated_at'           => $now,
		);

		if ( $row && 'open' === $row->status ) {
			if ( hash_equals( (string) $row->payload_hash, $hash ) ) {
				wp_send_json_success( array( 'captured' => true ) ); // Nothing changed.
			}
			Codex_Connector_Carts::update( $row->id, $fields );
			$cart_id = (string) $row->cart_id;
		} else {
			if ( $row ) {
				Codex_Connector_Carts::release_session( $session_key ); // Old purchased cart.
			}
			$cart_id = 'c_' . bin2hex( random_bytes( 16 ) );
			$ok      = Codex_Connector_Carts::insert(
				array_merge(
					$fields,
					array(
						'cart_id'     => $cart_id,
						'session_key' => $session_key,
						'token'       => bin2hex( random_bytes( 24 ) ),
						'created_at'  => $now,
					)
				)
			);
			if ( ! $ok ) {
				wp_send_json_error( array( 'code' => 'storage' ), 500 );
			}
		}

		Codex_Connector_Plugin::log( 'info', sprintf( 'Cart %s captured with consent (%s).', $cart_id, Codex_Connector_Plugin::mask_phone( $phone ) ) );
		Codex_Connector_Events::queue( 'cart.updated', array( 'cart_id' => $cart_id ) );

		wp_send_json_success( array( 'captured' => true ) );
	}

	/**
	 * @param string $session_key Session key.
	 * @return bool
	 */
	private static function within_rate_limit( $session_key ) {
		$key   = 'codex_rl_' . md5( $session_key );
		$state = get_transient( $key );
		if ( ! is_array( $state ) || ! isset( $state['start'], $state['count'] ) || ( time() - (int) $state['start'] ) > self::RATE_WINDOW ) {
			$state = array(
				'start' => time(),
				'count' => 0,
			);
		}
		++$state['count'];
		set_transient( $key, $state, self::RATE_WINDOW );
		return $state['count'] <= self::RATE_LIMIT;
	}

	/**
	 * Cart from WC()->cart: prices as shown at checkout (incl. tax when the store displays it).
	 *
	 * @return array{currency:string,total:string,items:array}
	 */
	private static function snapshot_cart() {
		$cart = WC()->cart;
		$cart->calculate_totals();

		$incl  = 'incl' === get_option( 'woocommerce_tax_display_cart' );
		$items = array();

		foreach ( $cart->get_cart() as $line ) {
			$product = isset( $line['data'] ) ? $line['data'] : null;
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$qty = max( 1, (int) $line['quantity'] );

			$line_total = (float) $line['line_total'] + ( $incl ? (float) $line['line_tax'] : 0.0 );
			$parent_id  = (int) $line['product_id'];
			$parent     = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
			$categories = $parent ? $parent->get_category_ids() : array();

			$items[] = array(
				'product_id'   => (string) $parent_id,
				'variation_id' => ! empty( $line['variation_id'] ) ? (string) (int) $line['variation_id'] : null,
				'name'         => wp_strip_all_tags( $product->get_name() ),
				'quantity'     => $qty,
				'unit_price'   => self::money( $line_total / $qty ),
				'category_id'  => ! empty( $categories ) ? (string) (int) reset( $categories ) : null,
				// Internal (stripped from the Cart object): needed to restore "any" variations.
				'_attributes'  => isset( $line['variation'] ) && is_array( $line['variation'] ) ? array_map( 'wc_clean', $line['variation'] ) : array(),
			);
		}

		return array(
			'currency' => get_woocommerce_currency(),
			'total'    => self::money( (float) $cart->get_total( 'edit' ) ),
			'items'    => $items,
		);
	}

	/**
	 * @param float $amount Amount.
	 * @return string "199.00" (the store's price decimals)
	 */
	private static function money( $amount ) {
		return Codex_Connector_Plugin::money( $amount );
	}

	/**
	 * Arab countries: calling code and the national mobile pattern (without
	 * the trunk 0). A local number is read in the customer's billing country,
	 * else the store's country.
	 *
	 * @var array<string, array{0: string, 1: string}>
	 */
	const MOBILE = array(
		'SA' => array( '966', '5\d{8}' ),
		'EG' => array( '20', '1[0125]\d{8}' ),
		'AE' => array( '971', '5\d{8}' ),
		'KW' => array( '965', '[569]\d{7}' ),
		'QA' => array( '974', '[3567]\d{7}' ),
		'BH' => array( '973', '[36]\d{7}' ),
		'OM' => array( '968', '[79]\d{7}' ),
		'JO' => array( '962', '7[789]\d{7}' ),
		'IQ' => array( '964', '7\d{9}' ),
		'LB' => array( '961', '(?:3\d{6}|7[01689]\d{6}|81\d{6})' ),
		'SY' => array( '963', '9\d{8}' ),
		'PS' => array( '970', '5[69]\d{7}' ),
		'YE' => array( '967', '7\d{8}' ),
		'MA' => array( '212', '[67]\d{8}' ),
		'DZ' => array( '213', '[567]\d{8}' ),
		'TN' => array( '216', '[2-9]\d{7}' ),
		'LY' => array( '218', '9[1-6]\d{7}' ),
		'SD' => array( '249', '[19]\d{8}' ),
		'MR' => array( '222', '[234]\d{7}' ),
	);

	/**
	 * The customer's country for reading a local number: billing, then shipping, then the store's.
	 *
	 * @return string ISO 3166-1 alpha-2.
	 */
	public static function phone_country() {
		$customer = function_exists( 'WC' ) && WC()->customer ? WC()->customer : null;
		$country  = $customer ? (string) $customer->get_billing_country() : '';
		if ( '' === $country && $customer ) {
			$country = (string) $customer->get_shipping_country();
		}
		if ( '' === $country && function_exists( 'WC' ) && WC()->countries ) {
			$country = (string) WC()->countries->get_base_country();
		}
		return strtoupper( $country );
	}

	/**
	 * Any typed phone → E.164, or null. Local numbers ("010…", "05…") use the
	 * country's calling code; known Arab countries must be mobiles. Accepts
	 * Arabic-Indic digits.
	 *
	 * @param string      $raw     As typed.
	 * @param string|null $country ISO country for local numbers; null = phone_country().
	 * @return string|null
	 */
	public static function normalize_phone( $raw, $country = null ) {
		$raw = strtr(
			(string) $raw,
			array(
				'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', // phpcs:ignore
				'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', // phpcs:ignore
			)
		);
		$raw  = trim( $raw );
		$plus = 0 === strpos( $raw, '+' );
		$d    = preg_replace( '/\D+/', '', $raw );
		if ( '' === $d ) {
			return null;
		}

		if ( ! $plus && 0 === strpos( $d, '00' ) ) {
			$d    = substr( $d, 2 );
			$plus = true;
		}

		if ( ! $plus ) {
			$country = strtoupper( null === $country ? self::phone_country() : (string) $country );
			if ( ! isset( self::MOBILE[ $country ] ) ) {
				return null; // Unknown country: only full international numbers.
			}
			list( $code, $mobile ) = self::MOBILE[ $country ];
			$national = ltrim( $d, '0' );
			// Typed with the country code but no "+" (e.g. 9665…, 2010…).
			if ( 0 === strpos( $d, $code ) && preg_match( '/^' . $mobile . '$/', substr( $d, strlen( $code ) ) ) ) {
				$national = substr( $d, strlen( $code ) );
			}
			$d = $code . $national;
		}

		// A known Arab country code must be followed by a mobile; allow a stray trunk 0 (+96605…, +2001…).
		foreach ( self::MOBILE as $rule ) {
			list( $code, $mobile ) = $rule;
			if ( 0 === strpos( $d, $code ) ) {
				$national = substr( $d, strlen( $code ) );
				if ( 0 === strpos( $national, '0' ) ) {
					$national = substr( $national, 1 );
				}
				return preg_match( '/^' . $mobile . '$/', $national ) ? '+' . $code . $national : null;
			}
		}

		return preg_match( '/^[1-9]\d{7,14}$/', $d ) ? '+' . $d : null;
	}

	/* -------------------------------------------------------------- purchase */

	/**
	 * @param int|WC_Order $order Order or id.
	 */
	public static function on_order_placed( $order ) {
		$order = is_object( $order ) ? $order : wc_get_order( $order );
		if ( ! $order || ! WC()->session ) {
			return;
		}
		$session_key = (string) WC()->session->get_customer_id();
		if ( '' === $session_key ) {
			return;
		}
		$row = Codex_Connector_Carts::find_by_session( $session_key );
		if ( ! $row || 'open' !== $row->status ) {
			return;
		}

		$order->update_meta_data( '_codex_cart_id', (string) $row->cart_id );
		$order->save_meta_data();

		if ( 1 !== (int) $row->consent ) {
			// Never sent to CODEX; no reason to keep it.
			Codex_Connector_Carts::delete( $row->id );
			return;
		}

		// Free the session for the next cart; the order meta keeps the link.
		Codex_Connector_Carts::update(
			$row->id,
			array(
				'order_id'    => $order->get_id(),
				'session_key' => null,
			)
		);

		if ( $order->is_paid() ) {
			self::on_order_paid( $order->get_id() );
		}
	}

	/**
	 * The order linked to a captured cart was paid: the cart is purchased.
	 *
	 * @param int|WC_Order $order Order or id.
	 */
	public static function on_order_paid( $order ) {
		$order = is_object( $order ) ? $order : wc_get_order( $order );
		if ( ! $order ) {
			return;
		}
		$cart_id = (string) $order->get_meta( '_codex_cart_id' );
		if ( '' === $cart_id ) {
			return;
		}
		$row = Codex_Connector_Carts::find( $cart_id );
		if ( ! $row || 'open' !== $row->status || 1 !== (int) $row->consent ) {
			return; // Already purchased (the hooks overlap), or never sent.
		}

		Codex_Connector_Carts::update(
			$row->id,
			array(
				'status'     => 'purchased',
				'order_id'   => $order->get_id(),
				'updated_at' => gmdate( 'Y-m-d H:i:s' ),
			)
		);
		Codex_Connector_Events::queue(
			'cart.purchased',
			array(
				'id'       => (string) $row->cart_id,
				'order_id' => (string) $order->get_id(),
			)
		);
	}
}
