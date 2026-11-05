<?php
/**
 * The WooCommerce → Settings → FirmAtlas tab.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab for keys, language, the VAT field and validation, with a connection test.
 */
final class Settings_Page extends \WC_Settings_Page {

	/**
	 * Registers the tab.
	 */
	public function __construct() {
		$this->id    = 'firmatlas';
		$this->label = __( 'FirmAtlas', 'firmatlas-for-woocommerce' );
		parent::__construct();
		add_action( 'woocommerce_admin_field_firmatlas_test', array( $this, 'test_button' ) );
	}

	/**
	 * The tab's fields.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'Company search at checkout', 'firmatlas-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => sprintf(
					/* translators: %s: link to firmatlas.be */
					__( 'Customers search Belgian companies by name or number in the company field, and the billing address fills itself. Get your keys at %s.', 'firmatlas-for-woocommerce' ),
					'<a href="https://firmatlas.be" target="_blank" rel="noopener">firmatlas.be</a>'
				),
				'id'    => 'firmatlas_keys',
			),
			array(
				'title'       => __( 'Publishable key', 'firmatlas-for-woocommerce' ),
				'desc'        => __( 'Starts with kbo_pk_live_. Used in the browser; allow this shop\'s address as an origin for the key.', 'firmatlas-for-woocommerce' ),
				'id'          => 'firmatlas_publishable_key',
				'type'        => 'text',
				'placeholder' => 'kbo_pk_live_…',
				'desc_tip'    => false,
			),
			array(
				'title'       => __( 'Secret key', 'firmatlas-for-woocommerce' ),
				'desc'        => __( 'Starts with kbo_sk_live_. Stays on this server; used to check VAT numbers.', 'firmatlas-for-woocommerce' ),
				'id'          => 'firmatlas_secret_key',
				'type'        => 'password',
				'placeholder' => 'kbo_sk_live_…',
			),
			array(
				'type' => 'firmatlas_test',
				'id'   => 'firmatlas_test',
			),
			array(
				'title'   => __( 'Company search', 'firmatlas-for-woocommerce' ),
				'desc'    => __( 'Search companies in the checkout\'s company field', 'firmatlas-for-woocommerce' ),
				'id'      => 'firmatlas_autocomplete',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Language', 'firmatlas-for-woocommerce' ),
				'desc'    => __( 'Language of company names, addresses and the search list.', 'firmatlas-for-woocommerce' ),
				'id'      => 'firmatlas_language',
				'type'    => 'select',
				'default' => 'auto',
				'options' => array(
					'auto' => __( 'Site language', 'firmatlas-for-woocommerce' ),
					'nl'   => 'Nederlands',
					'fr'   => 'Français',
					'de'   => 'Deutsch',
					'en'   => 'English',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'firmatlas_keys',
			),
			array(
				'title' => __( 'VAT number', 'firmatlas-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'firmatlas_vat',
			),
			array(
				'title'   => __( 'VAT number field', 'firmatlas-for-woocommerce' ),
				'desc'    => __( 'Add an optional VAT number field to checkout', 'firmatlas-for-woocommerce' ),
				'id'      => 'firmatlas_vat_field',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'When a Belgian VAT number is not valid', 'firmatlas-for-woocommerce' ),
				'desc'    => __( 'Checked against the KBO register and VIES. If the check cannot be done (the API or VIES is down), checkout always goes ahead. VAT numbers from other countries are not checked.', 'firmatlas-for-woocommerce' ),
				'id'      => 'firmatlas_vat_validation',
				'type'    => 'select',
				'default' => Settings::VALIDATION_NOTE,
				'options' => array(
					Settings::VALIDATION_NOTE  => __( 'Accept the order and add an order note', 'firmatlas-for-woocommerce' ),
					Settings::VALIDATION_BLOCK => __( 'Refuse the order until the number is fixed', 'firmatlas-for-woocommerce' ),
					Settings::VALIDATION_OFF   => __( 'Don\'t check', 'firmatlas-for-woocommerce' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'firmatlas_vat',
			),
			array(
				'title' => __( 'Advanced', 'firmatlas-for-woocommerce' ),
				'type'  => 'title',
				'id'    => 'firmatlas_advanced',
			),
			array(
				'title'       => __( 'API address', 'firmatlas-for-woocommerce' ),
				'desc'        => __( 'Leave empty for the hosted Firmatlas API. Set it to use a self-hosted engine behind your own gateway.', 'firmatlas-for-woocommerce' ),
				'id'          => 'firmatlas_api_url',
				'type'        => 'url',
				'placeholder' => Settings::API_URL,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'firmatlas_advanced',
			),
		);
	}

	/**
	 * Renders the connection test button.
	 *
	 * @return void
	 */
	public function test_button(): void {
		?>
		<tr>
			<th scope="row"></th>
			<td>
				<button type="button" class="button" id="firmatlas-test" data-nonce="<?php echo esc_attr( wp_create_nonce( 'firmatlas_test' ) ); ?>">
					<?php esc_html_e( 'Test connection', 'firmatlas-for-woocommerce' ); ?>
				</button>
				<span id="firmatlas-test-result" role="status" style="margin-left:8px"></span>
				<p class="description"><?php esc_html_e( 'Tests the saved secret key. Save first after changing it.', 'firmatlas-for-woocommerce' ); ?></p>
			</td>
		</tr>
		<?php
	}
}
