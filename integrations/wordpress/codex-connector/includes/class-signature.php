<?php
/**
 * HMAC signing exactly per docs/integrations/woocommerce-connector.md §2.
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Signature {

	const WINDOW = 300;

	/**
	 * "sha256=" + lowercase hex HMAC-SHA256.
	 *
	 * @param string $secret  Shared connector secret.
	 * @param string $message Signed string.
	 * @return string
	 */
	public static function sign( $secret, $message ) {
		return 'sha256=' . hash_hmac( 'sha256', $message, (string) $secret );
	}

	/**
	 * Plugin -> CODEX (POST): "{timestamp}.{raw_body}".
	 *
	 * @param int    $timestamp Unix seconds.
	 * @param string $raw_body  Exact body that is sent.
	 * @return string
	 */
	public static function post_string( $timestamp, $raw_body ) {
		return (int) $timestamp . '.' . $raw_body;
	}

	/**
	 * CODEX -> plugin (GET): "{timestamp}.GET.{route}?{query}".
	 *
	 * @param int    $timestamp Unix seconds.
	 * @param string $route     REST route without /wp-json, e.g. /codex/v1/carts.
	 * @param array  $query     Query parameters.
	 * @return string
	 */
	public static function get_string( $timestamp, $route, array $query ) {
		ksort( $query );
		$qs = http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
		return (int) $timestamp . '.GET.' . $route . '?' . $qs;
	}

	/**
	 * Verify a signed GET request from CODEX.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public static function verify_request( $request ) {
		$secret = Codex_Connector_Settings::get( 'secret' );
		if ( '' === $secret ) {
			return self::unauthorized();
		}

		$timestamp = (string) $request->get_header( 'X-Codex-Timestamp' );
		$signature = strtolower( trim( (string) $request->get_header( 'X-Codex-Signature' ) ) );

		if ( '' === $timestamp || ! ctype_digit( $timestamp ) || '' === $signature ) {
			return self::unauthorized();
		}
		if ( abs( time() - (int) $timestamp ) > self::WINDOW ) {
			return self::unauthorized();
		}

		$query = $request->get_query_params();
		// Plain-permalink sites route through ?rest_route=...; that is transport, not part of the signed query.
		unset( $query['rest_route'] );

		$expected = self::sign( $secret, self::get_string( (int) $timestamp, $request->get_route(), $query ) );

		if ( ! hash_equals( $expected, $signature ) ) {
			return self::unauthorized();
		}
		return true;
	}

	/**
	 * @return WP_Error
	 */
	private static function unauthorized() {
		return new WP_Error( 'unauthorized', 'Invalid or missing signature.', array( 'status' => 401 ) );
	}
}
