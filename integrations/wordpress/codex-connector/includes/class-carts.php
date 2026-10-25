<?php
/**
 * Storage for captured carts ({$wpdb->prefix}codex_carts) and the Cart object (§5).
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Carts {

	/**
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'codex_carts';
	}

	/**
	 * Create/upgrade the table (dbDelta).
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  cart_id varchar(64) NOT NULL,
  session_key varchar(191) DEFAULT NULL,
  token varchar(64) NOT NULL,
  status varchar(16) NOT NULL DEFAULT 'open',
  currency varchar(8) NOT NULL DEFAULT '',
  total varchar(32) NOT NULL DEFAULT '0.00',
  items longtext NOT NULL,
  phone varchar(20) DEFAULT NULL,
  consent tinyint(1) NOT NULL DEFAULT 0,
  consent_text text NULL,
  consent_text_version varchar(8) NOT NULL DEFAULT '',
  customer_id bigint(20) unsigned DEFAULT NULL,
  order_id bigint(20) unsigned DEFAULT NULL,
  payload_hash varchar(40) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY cart_id (cart_id),
  UNIQUE KEY session_key (session_key),
  UNIQUE KEY token (token),
  KEY updated_at (updated_at)
) {$charset};";

		dbDelta( $sql );
	}

	/**
	 * @param string $session_key WC session customer id.
	 * @return object|null
	 */
	public static function find_by_session( $session_key ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE session_key = %s", $session_key ) );
	}

	/**
	 * @param string $cart_id Plugin cart id (c_…).
	 * @return object|null
	 */
	public static function find( $cart_id ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE cart_id = %s", $cart_id ) );
	}

	/**
	 * @param string $token Recovery token.
	 * @return object|null
	 */
	public static function find_by_token( $token ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token = %s", $token ) );
	}

	/**
	 * @param array $data Column => value.
	 * @return bool
	 */
	public static function insert( array $data ) {
		global $wpdb;
		return false !== $wpdb->insert( self::table(), $data );
	}

	/**
	 * @param int   $id   Row id.
	 * @param array $data Column => value (null allowed).
	 * @return bool
	 */
	public static function update( $id, array $data ) {
		global $wpdb;
		return false !== $wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	/**
	 * @param int $id Row id.
	 */
	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Detach a session key from any row (frees the unique key).
	 *
	 * @param string $session_key Session key.
	 */
	public static function release_session( $session_key ) {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET session_key = NULL WHERE session_key = %s", $session_key ) );
	}

	/**
	 * Consented carts updated at or after $since, oldest first.
	 * Carts without consent are never exposed (§6).
	 *
	 * @param int $since    Unix seconds.
	 * @param int $page     1-based page.
	 * @param int $per_page ≤ 100.
	 * @return array{rows: array, has_more: bool}
	 */
	public static function list_since( $since, $page, $per_page ) {
		global $wpdb;
		$table  = self::table();
		$offset = ( $page - 1 ) * $per_page;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE consent = 1 AND phone IS NOT NULL AND updated_at >= %s ORDER BY updated_at ASC, id ASC LIMIT %d OFFSET %d",
				gmdate( 'Y-m-d H:i:s', $since ),
				$per_page + 1,
				$offset
			)
		);
		$rows     = is_array( $rows ) ? $rows : array();
		$has_more = count( $rows ) > $per_page;
		return array(
			'rows'     => array_slice( $rows, 0, $per_page ),
			'has_more' => $has_more,
		);
	}

	/**
	 * Daily cleanup: delete carts older than 30 days (§6).
	 */
	public static function cleanup() {
		global $wpdb;
		$table  = self::table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - CODEX_CONNECTOR_RETENTION_DAYS * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE updated_at < %s", $cutoff ) );
		Codex_Connector_Plugin::log( 'info', sprintf( 'Cleanup removed %d cart(s) older than %d days.', (int) $deleted, CODEX_CONNECTOR_RETENTION_DAYS ) );
	}

	/**
	 * DB datetime (UTC) -> ISO 8601.
	 *
	 * @param string $datetime Y-m-d H:i:s.
	 * @return string
	 */
	private static function iso_from_db( $datetime ) {
		$ts = strtotime( $datetime . ' UTC' );
		return Codex_Connector_Plugin::iso( false === $ts ? time() : $ts );
	}

	/**
	 * @param string $token Recovery token.
	 * @return string
	 */
	public static function recovery_url( $token ) {
		return add_query_arg( 'codex_cart', rawurlencode( $token ), home_url( '/' ) );
	}

	/**
	 * Row -> Cart object (§5). Internal fields (variation attributes) are stripped.
	 *
	 * @param object $row DB row.
	 * @return array
	 */
	public static function to_cart( $row ) {
		$items = json_decode( (string) $row->items, true );
		$out   = array();
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$out[] = array(
				'product_id'   => isset( $item['product_id'] ) ? (string) $item['product_id'] : '',
				'variation_id' => isset( $item['variation_id'] ) && null !== $item['variation_id'] ? (string) $item['variation_id'] : null,
				'name'         => isset( $item['name'] ) ? (string) $item['name'] : '',
				'quantity'     => isset( $item['quantity'] ) ? (int) $item['quantity'] : 0,
				'unit_price'   => isset( $item['unit_price'] ) ? (string) $item['unit_price'] : '0.00',
				'category_id'  => isset( $item['category_id'] ) && null !== $item['category_id'] ? (string) $item['category_id'] : null,
			);
		}

		$consent = 1 === (int) $row->consent;
		$updated = self::iso_from_db( $row->updated_at );

		return array(
			'id'                   => (string) $row->cart_id,
			'status'               => (string) $row->status,
			'currency'             => (string) $row->currency,
			'total'                => (string) $row->total,
			'items'                => $out,
			'phone'                => $consent && ! empty( $row->phone ) ? (string) $row->phone : null,
			'consent'              => $consent,
			'consent_text'         => (string) $row->consent_text,
			'consent_text_version' => (string) $row->consent_text_version,
			'recovery_url'         => self::recovery_url( (string) $row->token ),
			'customer_id'          => ! empty( $row->customer_id ) ? (string) $row->customer_id : null,
			'updated_at'           => $updated,
			'abandoned_at'         => $updated,
		);
	}
}
