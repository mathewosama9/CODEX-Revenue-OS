<?php
/**
 * ?codex_cart=<token>: restore a captured cart and open checkout (the Cart's recovery_url).
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Restore {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_restore' ), 5 );
	}

	public static function maybe_restore() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the token itself is the capability.
		if ( ! isset( $_GET['codex_cart'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = strtolower( sanitize_text_field( wp_unslash( $_GET['codex_cart'] ) ) );
		$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

		nocache_headers();

		if ( ! preg_match( '/^[a-f0-9]{48}$/', $token ) ) {
			self::redirect( $shop );
		}

		$row = Codex_Connector_Carts::find_by_token( $token );
		if ( ! $row || 'open' !== $row->status || self::is_expired( $row ) ) {
			self::redirect( $shop );
		}

		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
		if ( ! WC()->cart || ! WC()->session ) {
			self::redirect( $shop );
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$items = json_decode( (string) $row->items, true );
		WC()->cart->empty_cart();

		$added = 0;
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
			$variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
			$quantity     = isset( $item['quantity'] ) ? max( 1, (int) $item['quantity'] ) : 1;
			$attributes   = isset( $item['_attributes'] ) && is_array( $item['_attributes'] ) ? array_map( 'wc_clean', $item['_attributes'] ) : array();
			if ( ! $product_id ) {
				continue;
			}
			if ( $variation_id && empty( $attributes ) ) {
				$variation  = wc_get_product( $variation_id );
				$attributes = $variation ? $variation->get_variation_attributes() : array();
			}
			try {
				if ( WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes ) ) {
					++$added;
				}
			} catch ( Exception $e ) {
				continue; // Out of stock / no longer sold.
			}
		}

		if ( 0 === $added ) {
			wc_clear_notices();
			wc_add_notice( __( 'Sorry, the products in this cart are no longer available.', 'codex-connector' ), 'notice' );
			self::redirect( $shop );
		}

		// Tie the cart to this browser's session so the order sends cart.purchased.
		$session_key = (string) WC()->session->get_customer_id();
		if ( '' !== $session_key && $session_key !== (string) $row->session_key ) {
			Codex_Connector_Carts::release_session( $session_key );
			Codex_Connector_Carts::update( $row->id, array( 'session_key' => $session_key ) );
		}
		WC()->session->set( 'codex_restored_cart', (string) $row->cart_id );

		self::redirect( wc_get_checkout_url() );
	}

	/**
	 * @param object $row Cart row.
	 * @return bool
	 */
	private static function is_expired( $row ) {
		$updated = strtotime( $row->updated_at . ' UTC' );
		return false === $updated || ( time() - $updated ) > CODEX_CONNECTOR_RETENTION_DAYS * DAY_IN_SECONDS;
	}

	/**
	 * @param string $url URL.
	 */
	private static function redirect( $url ) {
		wp_safe_redirect( $url );
		exit;
	}
}
