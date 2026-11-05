<?php
/**
 * Plugin bootstrap.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into WordPress and WooCommerce.
 */
final class Plugin {

	private const VAT_CHECKS_PER_MINUTE = 20;

	/**
	 * Hooks everything once WooCommerce is loaded.
	 *
	 * @return void
	 */
	public static function boot(): void {
		if ( ! class_exists( \WooCommerce::class ) ) {
			return;
		}
		add_filter( 'woocommerce_get_settings_pages', array( self::class, 'settings_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FIRMATLAS_WC_FILE ), array( self::class, 'action_links' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'admin_script' ) );
		add_action( 'wp_ajax_firmatlas_test', array( self::class, 'test_connection' ) );
		add_action( 'rest_api_init', array( self::class, 'rest_routes' ) );
		Order_Meta::register();
		Checkout_Classic::register();
		Checkout_Blocks::register();
	}

	/**
	 * Adds the settings tab.
	 *
	 * @param array<int, \WC_Settings_Page> $pages Existing tabs.
	 * @return array<int, \WC_Settings_Page>
	 */
	public static function settings_page( array $pages ): array {
		require_once FIRMATLAS_WC_DIR . 'includes/class-settings-page.php';
		$pages[] = new Settings_Page();
		return $pages;
	}

	/**
	 * Adds a Settings link on the plugins screen.
	 *
	 * @param array<int|string, string> $links Existing links.
	 * @return array<int|string, string>
	 */
	public static function action_links( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=firmatlas' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'firmatlas-for-woocommerce' ) . '</a>' );
		return $links;
	}

	/**
	 * Loads the connection test script on the settings tab.
	 *
	 * @return void
	 */
	public static function admin_script(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only decides whether to load a script.
		if ( ! isset( $_GET['page'], $_GET['tab'] ) || 'wc-settings' !== $_GET['page'] || 'firmatlas' !== $_GET['tab'] ) {
			return;
		}
		wp_enqueue_script( 'firmatlas-admin', FIRMATLAS_WC_URL . 'assets/js/admin.js', array(), FIRMATLAS_WC_VERSION, true );
		wp_localize_script(
			'firmatlas-admin',
			'firmatlasAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'testing' => __( 'Testing…', 'firmatlas-for-woocommerce' ),
			)
		);
	}

	/**
	 * Answers the settings tab's connection test with the saved secret key.
	 *
	 * @return void
	 */
	public static function test_connection(): void {
		check_ajax_referer( 'firmatlas_test', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'firmatlas-for-woocommerce' ) ), 403 );
		}
		if ( '' === Settings::secret_key() ) {
			wp_send_json_error( array( 'message' => __( 'Add and save a secret key first.', 'firmatlas-for-woocommerce' ) ) );
		}
		$answer = Api::get( '/v1/enterprises/0403170701' );
		if ( is_wp_error( $answer ) ) {
			wp_send_json_error(
				array(
					/* translators: %s: error message */
					'message' => sprintf( __( 'The API could not be reached: %s', 'firmatlas-for-woocommerce' ), $answer->get_error_message() ),
				)
			);
		}
		if ( 200 !== $answer['status'] ) {
			wp_send_json_error( array( 'message' => Api::error_message( $answer, __( 'The API refused the request.', 'firmatlas-for-woocommerce' ) ) ) );
		}
		wp_send_json_success( array( 'message' => __( 'Connected. The secret key works.', 'firmatlas-for-woocommerce' ) ) );
	}

	/**
	 * Registers the VAT check the checkout scripts call while the customer types.
	 *
	 * @return void
	 */
	public static function rest_routes(): void {
		register_rest_route(
			'firmatlas/v1',
			'/vat',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_vat' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'number' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Checks one VAT number for the checkout, limited per visitor IP.
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response
	 */
	public static function rest_vat( \WP_REST_Request $request ): \WP_REST_Response {
		if ( Settings::VALIDATION_OFF === Settings::validation() ) {
			return new \WP_REST_Response(
				array(
					'status'  => Validator::UNKNOWN,
					'message' => '',
				)
			);
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'firmatlas_rl_' . md5( $ip . '|' . gmdate( 'YmdHi' ) );
		$count = (int) get_transient( $key );
		if ( $count >= self::VAT_CHECKS_PER_MINUTE ) {
			return new \WP_REST_Response(
				array(
					'status'  => Validator::UNKNOWN,
					'message' => '',
				),
				429
			);
		}
		set_transient( $key, $count + 1, 2 * MINUTE_IN_SECONDS );
		$result = Validator::check( (string) $request->get_param( 'number' ) );
		return new \WP_REST_Response(
			array(
				'status'  => $result['status'],
				'message' => $result['message'],
			)
		);
	}

	/**
	 * Registers the bundled widget script; checkouts enqueue it.
	 *
	 * @return void
	 */
	public static function register_widget(): void {
		wp_register_script(
			'firmatlas-autocomplete',
			FIRMATLAS_WC_URL . 'assets/js/firmatlas-autocomplete.iife.js',
			array(),
			'0.1.0',
			array( 'in_footer' => true )
		);
	}

	/**
	 * The settings the checkout scripts need.
	 *
	 * @return array<string, mixed>
	 */
	public static function script_config(): array {
		return array(
			'key'        => Settings::publishable_key(),
			'lang'       => Settings::language(),
			'endpoint'   => Settings::api_url() . '/v1/autocomplete',
			'vatField'   => Settings::vat_field_enabled(),
			'validation' => Settings::validation(),
			'vatUrl'     => rest_url( 'firmatlas/v1/vat' ),
			'i18n'       => array( 'valid' => __( 'Valid VAT number', 'firmatlas-for-woocommerce' ) ),
			'box'        => array(
				'nl' => 'bus',
				'fr' => 'boîte',
				'de' => 'Bf.',
				'en' => 'box',
			)[ Settings::language() ],
		);
	}
}
