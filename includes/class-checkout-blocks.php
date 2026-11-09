<?php
/**
 * The block checkout.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the VAT field (an additional checkout field), the company search and
 * VAT validation to the block checkout.
 */
final class Checkout_Blocks {

	public const FIELD = 'firmatlas/vat-number';

	/**
	 * Hooks the block checkout.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'woocommerce_init', array( self::class, 'field' ) );
		add_action( 'woocommerce_set_additional_field_value', array( self::class, 'save' ), 10, 4 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( Order_Meta::class, 'note_if_invalid' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'scripts' ) );
	}

	/**
	 * Reports whether the current page uses the checkout block.
	 *
	 * @return bool
	 */
	public static function page_has_block(): bool {
		$id = get_queried_object_id();
		return $id > 0 && has_block( 'woocommerce/checkout', $id );
	}

	/**
	 * Registers the VAT number as an optional contact field.
	 *
	 * @return void
	 */
	public static function field(): void {
		if ( ! Settings::vat_field_enabled() || ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'                => self::FIELD,
				'label'             => __( 'VAT number', 'firmatlas-for-woocommerce' ),
				'optionalLabel'     => __( 'VAT number (optional)', 'firmatlas-for-woocommerce' ),
				'location'          => 'contact',
				'type'              => 'text',
				'required'          => false,
				'attributes'        => array( 'autocomplete' => 'off' ),
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'validate_callback' => array( self::class, 'validate' ),
			)
		);
	}

	/**
	 * Normalizes the typed VAT number.
	 *
	 * @param mixed $value What the customer typed.
	 * @return string
	 */
	public static function sanitize( $value ): string {
		$vat    = Vat::normalize( sanitize_text_field( (string) $value ) );
		$digits = Vat::belgian_digits( $vat );
		return null !== $digits && Vat::checksum_ok( $digits ) ? Vat::vat_number( $digits ) : $vat;
	}

	/**
	 * Refuses an invalid Belgian VAT number when the setting says so.
	 *
	 * @param mixed $value The sanitized value.
	 * @return \WP_Error|null
	 */
	public static function validate( $value ) {
		if ( Settings::VALIDATION_BLOCK !== Settings::validation() ) {
			return null;
		}
		$result = Validator::check( (string) $value );
		if ( Validator::INVALID === $result['status'] ) {
			return new \WP_Error( 'firmatlas_invalid_vat', $result['message'] );
		}
		return null;
	}

	/**
	 * Writes the documented order meta when WooCommerce stores the field on an order.
	 *
	 * @param string   $key     Field id.
	 * @param mixed    $value   Field value.
	 * @param string   $group   Field group.
	 * @param \WC_Data $wc_data The order or customer.
	 * @return void
	 */
	public static function save( $key, $value, $group, $wc_data ): void {
		if ( self::FIELD !== $key || ! $wc_data instanceof \WC_Order ) {
			return;
		}
		Order_Meta::apply( $wc_data, (string) $value );
	}

	/**
	 * Loads the widget and the block checkout script on a page with the checkout block.
	 *
	 * @return void
	 */
	public static function scripts(): void {
		if ( ! Settings::autocomplete_enabled() || ! self::page_has_block() || is_order_received_page() ) {
			return;
		}
		Plugin::register_widget();
		wp_enqueue_script(
			'firmatlas-blocks',
			FIRMATLAS_WC_URL . 'assets/js/checkout-blocks.js',
			array( 'firmatlas-autocomplete', 'wp-data' ),
			FIRMATLAS_WC_VERSION,
			array( 'in_footer' => true )
		);
		wp_localize_script( 'firmatlas-blocks', 'firmatlasCheckout', Plugin::script_config() + array( 'field' => self::FIELD ) );
	}
}
