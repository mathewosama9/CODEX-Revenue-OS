<?php
/**
 * WooCommerce → CODEX Connector settings page.
 *
 * @package CodexConnector
 */

defined( 'ABSPATH' ) || exit;

final class Codex_Connector_Settings {

	const PAGE       = 'codex-connector';
	const GROUP      = 'codex_connector';
	const CAPABILITY = 'manage_woocommerce';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_codex_connector_test', array( __CLASS__, 'handle_test' ) );
		add_filter( 'option_page_capability_' . self::GROUP, array( __CLASS__, 'capability' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( CODEX_CONNECTOR_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * @return string
	 */
	public static function capability() {
		return self::CAPABILITY;
	}

	/**
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'codex-connector' ) . '</a>' );
		return $links;
	}

	/**
	 * Raw stored settings.
	 *
	 * @return array
	 */
	private static function all() {
		$opts = get_option( CODEX_CONNECTOR_OPTION, array() );
		return wp_parse_args(
			is_array( $opts ) ? $opts : array(),
			array(
				'api_base'     => '',
				'store_id'     => '',
				'secret'       => '',
				'consent_text' => '',
			)
		);
	}

	/**
	 * @param string $key api_base|store_id|secret|consent_text.
	 * @return string
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? (string) $all[ $key ] : '';
	}

	/**
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::get( 'api_base' ) && '' !== self::get( 'store_id' ) && '' !== self::get( 'secret' );
	}

	/**
	 * Default consent text (protocol §5). Translatable so English visitors see English;
	 * the Arabic translation is exactly the protocol text.
	 *
	 * @return string
	 */
	public static function default_consent_text() {
		return __( 'I agree to receive WhatsApp messages from the store about my order and offers.', 'codex-connector' );
	}

	/**
	 * The consent text shown to the customer right now.
	 *
	 * @return string
	 */
	public static function consent_text() {
		$text = trim( self::get( 'consent_text' ) );
		return '' !== $text ? $text : self::default_consent_text();
	}

	/**
	 * First 8 hex of sha1(consent_text).
	 *
	 * @param string $text Consent text.
	 * @return string
	 */
	public static function consent_text_version( $text ) {
		return substr( sha1( $text ), 0, 8 );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'CODEX Connector', 'codex-connector' ),
			__( 'CODEX Connector', 'codex-connector' ),
			self::CAPABILITY,
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			self::GROUP,
			CODEX_CONNECTOR_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section( 'codex_connection', __( 'Connection', 'codex-connector' ), array( __CLASS__, 'section_connection' ), self::PAGE );
		add_settings_field( 'api_base', __( 'API base URL', 'codex-connector' ), array( __CLASS__, 'field_api_base' ), self::PAGE, 'codex_connection', array( 'label_for' => 'codex_api_base' ) );
		add_settings_field( 'store_id', __( 'Store ID', 'codex-connector' ), array( __CLASS__, 'field_store_id' ), self::PAGE, 'codex_connection', array( 'label_for' => 'codex_store_id' ) );
		add_settings_field( 'secret', __( 'Connector secret', 'codex-connector' ), array( __CLASS__, 'field_secret' ), self::PAGE, 'codex_connection', array( 'label_for' => 'codex_secret' ) );

		add_settings_section( 'codex_consent', __( 'WhatsApp consent at checkout', 'codex-connector' ), array( __CLASS__, 'section_consent' ), self::PAGE );
		add_settings_field( 'consent_text', __( 'Consent checkbox text', 'codex-connector' ), array( __CLASS__, 'field_consent_text' ), self::PAGE, 'codex_consent', array( 'label_for' => 'codex_consent_text' ) );
	}

	/**
	 * Settings API sanitize callback. The secret is kept when the field is left blank.
	 *
	 * @param mixed $input Posted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$old   = self::all();
		$input = is_array( $input ) ? $input : array();
		$out   = $old;

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $old;
		}

		// API base URL: https only, no trailing slash.
		$api = isset( $input['api_base'] ) ? trim( (string) $input['api_base'] ) : '';
		if ( '' === $api ) {
			$out['api_base'] = '';
		} else {
			$api = esc_url_raw( $api, array( 'https' ) );
			if ( '' === $api || 'https' !== wp_parse_url( $api, PHP_URL_SCHEME ) ) {
				add_settings_error( CODEX_CONNECTOR_OPTION, 'api_base', __( 'The API base URL must start with https://', 'codex-connector' ) );
			} else {
				$out['api_base'] = untrailingslashit( $api );
			}
		}

		// Store ID: woo_<ulid lowercase>.
		$store = isset( $input['store_id'] ) ? strtolower( sanitize_text_field( (string) $input['store_id'] ) ) : '';
		if ( '' === $store || preg_match( '/^woo_[a-z0-9]{10,60}$/', $store ) ) {
			$out['store_id'] = $store;
		} else {
			add_settings_error( CODEX_CONNECTOR_OPTION, 'store_id', __( 'The Store ID looks wrong. Copy it again from CODEX (it starts with woo_).', 'codex-connector' ) );
		}

		// Secret: 64 hex chars; blank keeps the saved one.
		$secret = isset( $input['secret'] ) ? trim( (string) $input['secret'] ) : '';
		if ( '' !== $secret ) {
			if ( preg_match( '/^[a-f0-9]{64}$/i', $secret ) ) {
				$out['secret'] = strtolower( $secret );
			} else {
				add_settings_error( CODEX_CONNECTOR_OPTION, 'secret', __( 'The connector secret must be the 64-character code shown in CODEX. The saved secret was kept.', 'codex-connector' ) );
			}
		}
		if ( ! empty( $input['clear_secret'] ) ) {
			$out['secret'] = '';
		}

		// Consent text: plain text; blank = translated default.
		$consent = isset( $input['consent_text'] ) ? sanitize_textarea_field( (string) $input['consent_text'] ) : '';
		$consent = trim( $consent );
		if ( $consent === self::default_consent_text() ) {
			$consent = '';
		}
		$out['consent_text'] = mb_substr( $consent, 0, 500 );

		return $out;
	}

	public static function section_connection() {
		echo '<p>' . esc_html__( 'Paste the values shown in CODEX → Onboarding → Connect store → WooCommerce.', 'codex-connector' ) . '</p>';
	}

	public static function section_consent() {
		echo '<p>' . esc_html__( 'An unticked checkbox is added to checkout. The cart and phone are sent to CODEX only when the customer ticks it. Have a lawyer review this text before going live.', 'codex-connector' ) . '</p>';
	}

	public static function field_api_base() {
		printf(
			'<input type="url" class="regular-text code" id="codex_api_base" name="%1$s[api_base]" value="%2$s" placeholder="https://" autocomplete="off" />',
			esc_attr( CODEX_CONNECTOR_OPTION ),
			esc_attr( self::get( 'api_base' ) )
		);
	}

	public static function field_store_id() {
		printf(
			'<input type="text" class="regular-text code" id="codex_store_id" name="%1$s[store_id]" value="%2$s" placeholder="woo_…" autocomplete="off" />',
			esc_attr( CODEX_CONNECTOR_OPTION ),
			esc_attr( self::get( 'store_id' ) )
		);
	}

	public static function field_secret() {
		$saved = '' !== self::get( 'secret' );
		// Never echo the secret back: the value is always empty.
		printf(
			'<input type="password" class="regular-text code" id="codex_secret" name="%1$s[secret]" value="" placeholder="%2$s" autocomplete="new-password" />',
			esc_attr( CODEX_CONNECTOR_OPTION ),
			esc_attr( $saved ? __( 'Saved — leave blank to keep it', 'codex-connector' ) : __( 'Paste the secret from CODEX', 'codex-connector' ) )
		);
		if ( $saved ) {
			printf(
				'<p><label><input type="checkbox" name="%1$s[clear_secret]" value="1" /> %2$s</label></p>',
				esc_attr( CODEX_CONNECTOR_OPTION ),
				esc_html__( 'Remove the saved secret', 'codex-connector' )
			);
		}
	}

	public static function field_consent_text() {
		printf(
			'<textarea class="large-text" rows="3" id="codex_consent_text" name="%1$s[consent_text]" placeholder="%2$s">%3$s</textarea><p class="description">%4$s</p>',
			esc_attr( CODEX_CONNECTOR_OPTION ),
			esc_attr( self::default_consent_text() ),
			esc_textarea( self::get( 'consent_text' ) ),
			esc_html__( 'Leave blank to use the default text (Arabic on Arabic sites, English on English sites).', 'codex-connector' )
		);
	}

	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'codex-connector' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CODEX Connector', 'codex-connector' ); ?></h1>
			<?php
			settings_errors( CODEX_CONNECTOR_OPTION );
			self::render_test_result();
			?>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Test connection', 'codex-connector' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: checkout type (classic or blocks). */
					esc_html__( 'Sends a signed test event to CODEX. Detected checkout: %s.', 'codex-connector' ),
					'<code>' . esc_html( Codex_Connector_Plugin::checkout_type() ) . '</code>'
				);
				?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="codex_connector_test" />
				<?php wp_nonce_field( 'codex_connector_test' ); ?>
				<?php submit_button( __( 'Test connection', 'codex-connector' ), 'secondary', 'submit', false, self::is_configured() ? array() : array( 'disabled' => 'disabled' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function render_test_result() {
		$key    = 'codex_connector_test_' . get_current_user_id();
		$result = get_transient( $key );
		if ( ! is_array( $result ) ) {
			return;
		}
		delete_transient( $key );

		$ok    = ! empty( $result['ok'] );
		$class = $ok ? 'notice-success' : 'notice-error';
		$title = $ok ? __( 'Connected to CODEX.', 'codex-connector' ) : __( 'Connection failed.', 'codex-connector' );

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p><strong>' . esc_html( $title ) . '</strong> ';
		if ( ! empty( $result['code'] ) ) {
			/* translators: %d: HTTP status code. */
			echo esc_html( sprintf( __( 'HTTP %d', 'codex-connector' ), (int) $result['code'] ) ) . ' ';
		}
		if ( ! empty( $result['message'] ) ) {
			echo '<code>' . esc_html( $result['message'] ) . '</code>';
		}
		echo '</p></div>';
	}

	/**
	 * "Test connection": signed connector.ping, synchronously, result shown on the page.
	 */
	public static function handle_test() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'codex-connector' ), 403 );
		}
		check_admin_referer( 'codex_connector_test' );

		if ( ! self::is_configured() ) {
			$result = array(
				'ok'      => false,
				'code'    => 0,
				'message' => __( 'Save the API base URL, Store ID and secret first.', 'codex-connector' ),
			);
		} else {
			$res    = Codex_Connector_Events::send_now( 'connector.ping', Codex_Connector_Events::ping_data() );
			$result = array(
				'ok'      => 200 === $res['code'],
				'code'    => $res['code'],
				'message' => mb_substr( $res['error'] ? $res['error'] : $res['body'], 0, 300 ),
			);
		}

		set_transient( 'codex_connector_test_' . get_current_user_id(), $result, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}
}
