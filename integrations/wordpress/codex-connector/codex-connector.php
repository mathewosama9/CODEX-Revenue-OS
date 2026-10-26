<?php
/**
 * Plugin Name:          CODEX Connector
 * Plugin URI:           https://codex.sa/
 * Description:          Connects your WooCommerce store to CODEX: captures abandoned carts at checkout (only with the customer's WhatsApp consent) and keeps CODEX in sync with orders and products.
 * Version:              1.1.1
 * Requires at least:    6.0
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               CODEX
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          codex-connector
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      9.9
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

define( 'CODEX_CONNECTOR_VERSION', '1.1.1' );
define( 'CODEX_CONNECTOR_DB_VERSION', '1' );
define( 'CODEX_CONNECTOR_FILE', __FILE__ );
define( 'CODEX_CONNECTOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'CODEX_CONNECTOR_URL', plugin_dir_url( __FILE__ ) );
define( 'CODEX_CONNECTOR_OPTION', 'codex_connector_settings' );
define( 'CODEX_CONNECTOR_CLEANUP_HOOK', 'codex_connector_daily_cleanup' );
define( 'CODEX_CONNECTOR_SEND_HOOK', 'codex_connector_send_event' );
define( 'CODEX_CONNECTOR_AS_GROUP', 'codex-connector' );
define( 'CODEX_CONNECTOR_RETENTION_DAYS', 30 );

/**
 * Autoloader: Codex_Connector_Cart_Capture -> includes/class-cart-capture.php
 */
spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'Codex_Connector_' ) ) {
			return;
		}
		$slug = strtolower( str_replace( '_', '-', substr( $class, strlen( 'Codex_Connector_' ) ) ) );
		$file = CODEX_CONNECTOR_DIR . 'includes/class-' . $slug . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Declare compatibility with HPOS and the Cart/Checkout blocks.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CODEX_CONNECTOR_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CODEX_CONNECTOR_FILE, true );
		}
	}
);

register_activation_hook( __FILE__, array( 'Codex_Connector_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Codex_Connector_Plugin', 'deactivate' ) );

/**
 * Bootstrap and shared helpers.
 */
final class Codex_Connector_Plugin {

	/**
	 * Wire everything once WooCommerce is loaded.
	 */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ), 1 );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_missing_woocommerce' ) );
			return;
		}

		self::maybe_upgrade_db();

		Codex_Connector_Settings::init();
		Codex_Connector_Events::init();
		Codex_Connector_Cart_Capture::init();
		Codex_Connector_Restore::init();
		Codex_Connector_Rest::init();

		add_action( CODEX_CONNECTOR_CLEANUP_HOOK, array( 'Codex_Connector_Carts', 'cleanup' ) );
		add_action( 'init', array( __CLASS__, 'ensure_cleanup_scheduled' ) );
	}

	public static function load_textdomain() {
		load_plugin_textdomain( 'codex-connector', false, dirname( plugin_basename( CODEX_CONNECTOR_FILE ) ) . '/languages' );
	}

	public static function notice_missing_woocommerce() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'CODEX Connector needs WooCommerce to be installed and active.', 'codex-connector' ) . '</p></div>';
	}

	/**
	 * Activation: create the carts table and schedule the daily cleanup.
	 */
	public static function activate() {
		Codex_Connector_Carts::install();
		update_option( 'codex_connector_db_version', CODEX_CONNECTOR_DB_VERSION, false );
		self::ensure_cleanup_scheduled();
	}

	/**
	 * Deactivation: tell CODEX (best effort, synchronous) and unschedule our jobs.
	 * Nothing is deleted.
	 */
	public static function deactivate() {
		if ( Codex_Connector_Settings::is_configured() ) {
			Codex_Connector_Events::send_now( 'plugin.deactivated', new stdClass() );
		}

		wp_clear_scheduled_hook( CODEX_CONNECTOR_CLEANUP_HOOK );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( CODEX_CONNECTOR_SEND_HOOK, array(), CODEX_CONNECTOR_AS_GROUP );
			as_unschedule_all_actions( CODEX_CONNECTOR_SEND_HOOK );
		}
	}

	public static function maybe_upgrade_db() {
		if ( get_option( 'codex_connector_db_version' ) !== CODEX_CONNECTOR_DB_VERSION ) {
			Codex_Connector_Carts::install();
			update_option( 'codex_connector_db_version', CODEX_CONNECTOR_DB_VERSION, false );
		}
	}

	public static function ensure_cleanup_scheduled() {
		if ( ! wp_next_scheduled( CODEX_CONNECTOR_CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', CODEX_CONNECTOR_CLEANUP_HOOK );
		}
	}

	/**
	 * "classic" or "blocks", based on the content of the checkout page.
	 *
	 * @return string
	 */
	public static function checkout_type() {
		$page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
		if ( $page_id > 0 && function_exists( 'has_block' ) && has_block( 'woocommerce/checkout', $page_id ) ) {
			return 'blocks';
		}
		return 'classic';
	}

	/**
	 * Log without PII. Context must never contain phones, names or secrets.
	 *
	 * @param string $level   info|warning|error.
	 * @param string $message Message (already free of PII).
	 */
	public static function log( $level, $message ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->log( $level, $message, array( 'source' => 'codex-connector' ) );
	}

	/**
	 * +966551234567 -> +9665******67.
	 *
	 * @param string|null $phone Phone.
	 * @return string
	 */
	public static function mask_phone( $phone ) {
		$phone = (string) $phone;
		$len   = strlen( $phone );
		if ( $len <= 6 ) {
			return str_repeat( '*', $len );
		}
		return substr( $phone, 0, 5 ) . str_repeat( '*', $len - 7 ) . substr( $phone, -2 );
	}

	/**
	 * Unix time -> ISO 8601 UTC ("2026-10-28T10:15:00Z").
	 *
	 * @param int $time Unix seconds.
	 * @return string
	 */
	public static function iso( $time ) {
		return gmdate( 'Y-m-d\TH:i:s\Z', (int) $time );
	}

	/**
	 * The store's price decimals (WooCommerce → Settings → General): 2 for
	 * SAR or EGP, 3 for KWD, 0 for IQD. Protocol v1.1 §5.
	 *
	 * @return int
	 */
	public static function price_decimals() {
		return function_exists( 'wc_get_price_decimals' ) ? max( 0, (int) wc_get_price_decimals() ) : 2;
	}

	/**
	 * A money string in the store currency with the store's decimals.
	 *
	 * @param float $amount Amount.
	 * @return string "199.00", "12.345" or "25000"
	 */
	public static function money( $amount ) {
		return number_format( (float) $amount, self::price_decimals(), '.', '' );
	}
}

add_action( 'plugins_loaded', array( 'Codex_Connector_Plugin', 'boot' ), 20 );
