<?php
/**
 * The classic (shortcode) checkout.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the VAT field, the company search and VAT validation to the classic checkout.
 */
final class Checkout_Classic {

	public const FIELD = 'billing_vat_number';

	/**
	 * Hooks the classic checkout.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'woocommerce_billing_fields', array( self::class, 'fields' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'save' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( self::class, 'processed' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'scripts' ) );
	}

	/**
	 * Adds the VAT number field after the company field.
	 *
	 * @param array<string, array<string, mixed>> $fields Billing fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields( array $fields ): array {
		if ( ! Settings::vat_field_enabled() ) {
			return $fields;
		}
		$fields[ self::FIELD ] = array(
			'label'        => __( 'VAT number', 'firmatlas-for-woocommerce' ),
			'placeholder'  => 'BE0123.456.749',
			'required'     => false,
			'class'        => array( 'form-row-wide' ),
			'autocomplete' => 'off',
			'priority'     => 31,
		);
		return $fields;
	}

	/**
	 * Refuses the order when the setting says so and the Belgian VAT number is invalid.
	 *
	 * @param array<string, mixed> $data   Posted checkout data.
	 * @param \WP_Error            $errors Checkout errors.
	 * @return void
	 */
	public static function validate( array $data, \WP_Error $errors ): void {
		if ( ! Settings::vat_field_enabled() || Settings::VALIDATION_BLOCK !== Settings::validation() ) {
			return;
		}
		$result = Validator::check( (string) ( $data[ self::FIELD ] ?? '' ) );
		if ( Validator::INVALID === $result['status'] ) {
			$errors->add( 'validation', $result['message'], array( 'id' => self::FIELD ) );
		}
	}

	/**
	 * Writes the VAT and enterprise numbers to the order.
	 *
	 * @param \WC_Order            $order The order being created.
	 * @param array<string, mixed> $data  Posted checkout data.
	 * @return void
	 */
	public static function save( \WC_Order $order, array $data ): void {
		if ( Settings::vat_field_enabled() ) {
			Order_Meta::apply( $order, (string) ( $data[ self::FIELD ] ?? '' ) );
		}
	}

	/**
	 * Adds the order note for an invalid number once the order is saved.
	 *
	 * @param int                  $order_id Order id.
	 * @param array<string, mixed> $data     Posted data.
	 * @param \WC_Order            $order    The order.
	 * @return void
	 */
	public static function processed( $order_id, $data, $order ): void {
		if ( $order instanceof \WC_Order ) {
			Order_Meta::note_if_invalid( $order );
		}
	}

	/**
	 * Loads the widget and the classic checkout script on a shortcode checkout.
	 *
	 * @return void
	 */
	public static function scripts(): void {
		if ( ! is_checkout() || is_order_received_page() || Checkout_Blocks::page_has_block() ) {
			return;
		}
		if ( ! Settings::autocomplete_enabled() && ( ! Settings::vat_field_enabled() || Settings::VALIDATION_OFF === Settings::validation() ) ) {
			return;
		}
		Plugin::register_widget();
		$deps = Settings::autocomplete_enabled() ? array( 'jquery', 'firmatlas-autocomplete' ) : array( 'jquery' );
		wp_enqueue_script( 'firmatlas-classic', FIRMATLAS_WC_URL . 'assets/js/checkout-classic.js', $deps, FIRMATLAS_WC_VERSION, array( 'in_footer' => true ) );
		wp_localize_script(
			'firmatlas-classic',
			'firmatlasCheckout',
			Plugin::script_config() + array( 'autocomplete' => Settings::autocomplete_enabled() )
		);
		wp_enqueue_style( 'firmatlas-checkout', FIRMATLAS_WC_URL . 'assets/css/checkout.css', array(), FIRMATLAS_WC_VERSION );
	}
}
