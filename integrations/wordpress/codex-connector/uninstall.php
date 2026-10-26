<?php
/**
 * Uninstall: drop the carts table and delete our options and schedules.
 *
 * @package CodexConnector
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$codex_table = $wpdb->prefix . 'codex_carts';
$wpdb->query( "DROP TABLE IF EXISTS `{$codex_table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

delete_option( 'codex_connector_settings' );
delete_option( 'codex_connector_db_version' );

// Rate-limit and test-result transients.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_codex\\_rl\\_%' OR option_name LIKE '\\_transient\\_timeout\\_codex\\_rl\\_%' OR option_name LIKE '\\_transient\\_codex\\_connector\\_test\\_%' OR option_name LIKE '\\_transient\\_timeout\\_codex\\_connector\\_test\\_%'" ); // phpcs:ignore

wp_clear_scheduled_hook( 'codex_connector_daily_cleanup' );
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'codex_connector_send_event' );
}
