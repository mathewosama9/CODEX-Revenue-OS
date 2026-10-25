<?php
/**
 * CODEX → plugin questions (protocol §4): codex/v1/ping, /carts, /carts/{id}.
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Rest {

	const NS = 'codex/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$auth = array( 'Codex_Connector_Signature', 'verify_request' );

		register_rest_route(
			self::NS,
			'/ping',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'ping' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NS,
			'/carts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'carts' ),
				'permission_callback' => $auth,
				'args'                => array(
					'since'    => array(
						'type'              => 'string',
						'required'          => false,
						'validate_callback' => array( __CLASS__, 'validate_since' ),
					),
					'page'     => array(
						'type'    => 'integer',
						'minimum' => 1,
						'default' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 100,
						'default' => 50,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/carts/(?P<id>[A-Za-z0-9_-]{1,64})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'cart' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * @param mixed $value Value.
	 * @return bool
	 */
	public static function validate_since( $value ) {
		return is_string( $value ) && false !== strtotime( $value );
	}

	/**
	 * @return WP_REST_Response
	 */
	public static function ping() {
		$data = Codex_Connector_Events::ping_data();
		return new WP_REST_Response(
			array(
				'ok'                  => true,
				'plugin_version'      => CODEX_CONNECTOR_VERSION,
				'woocommerce_version' => $data['woocommerce_version'],
				'currency'            => $data['currency'],
				'checkout'            => $data['checkout'],
			),
			200
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function carts( $request ) {
		$since    = $request->get_param( 'since' );
		$since_ts = $since ? strtotime( (string) $since ) : 0;
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		$result = Codex_Connector_Carts::list_since( (int) $since_ts, $page, $per_page );
		$carts  = array_map( array( 'Codex_Connector_Carts', 'to_cart' ), $result['rows'] );

		return new WP_REST_Response(
			array(
				'carts'     => array_values( $carts ),
				'next_page' => $result['has_more'] ? $page + 1 : null,
			),
			200
		);
	}

	/**
	 * Only consented carts exist for CODEX; anything else is a 404.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function cart( $request ) {
		$row = Codex_Connector_Carts::find( (string) $request['id'] );
		if ( ! $row || 1 !== (int) $row->consent || empty( $row->phone ) ) {
			return new WP_REST_Response( array( 'code' => 'not_found' ), 404 );
		}
		return new WP_REST_Response( Codex_Connector_Carts::to_cart( $row ), 200 );
	}
}
