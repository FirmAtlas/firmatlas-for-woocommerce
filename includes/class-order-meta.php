<?php
/**
 * Order meta written for invoicing plugins.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Writes the documented order meta and shows it in the admin.
 *
 * - `_billing_vat_number`: the VAT number as typed, normalized (`BE0403170701`).
 * - `_kbo_enterprise_number`: the 10-digit enterprise number (`0403170701`), for Belgian numbers that did not fail the check.
 * - `_firmatlas_vat_status`: valid, invalid, unknown or foreign, from the check at checkout.
 * - `_firmatlas_peppol`: yes, no or unknown, whether the company receives Peppol invoices, for Belgian numbers.
 */
final class Order_Meta {

	public const VAT        = '_billing_vat_number';
	public const ENTERPRISE = '_kbo_enterprise_number';
	public const STATUS     = '_firmatlas_vat_status';
	public const PEPPOL     = '_firmatlas_peppol';

	/**
	 * Hooks the admin display.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( self::class, 'show' ) );
	}

	/**
	 * Stores the VAT number, enterprise number and check result on an order (not saved here).
	 *
	 * @param \WC_Order $order The order.
	 * @param string    $raw   The VAT number as typed.
	 * @return array{status:string, message:string, digits:?string, peppol:string} The check result.
	 */
	public static function apply( \WC_Order $order, string $raw ): array {
		$vat = Vat::normalize( $raw );
		if ( '' === $vat ) {
			$order->delete_meta_data( self::VAT );
			$order->delete_meta_data( self::ENTERPRISE );
			$order->delete_meta_data( self::STATUS );
			$order->delete_meta_data( self::PEPPOL );
			return array(
				'status'  => Validator::EMPTY,
				'message' => '',
				'digits'  => null,
				'peppol'  => Validator::PEPPOL_UNKNOWN,
			);
		}
		$result = Settings::VALIDATION_OFF === Settings::validation()
			? array(
				'status'  => Validator::UNKNOWN,
				'message' => '',
				'digits'  => Vat::belgian_digits( $raw ),
				'peppol'  => Validator::PEPPOL_UNKNOWN,
			)
			: Validator::check( $raw );
		$digits = $result['digits'];
		$order->update_meta_data( self::VAT, null !== $digits ? Vat::vat_number( $digits ) : $vat );
		if ( null !== $digits && Vat::checksum_ok( $digits ) && Validator::INVALID !== $result['status'] ) {
			$order->update_meta_data( self::ENTERPRISE, $digits );
			$order->update_meta_data( self::PEPPOL, $result['peppol'] );
		} else {
			$order->delete_meta_data( self::ENTERPRISE );
			$order->delete_meta_data( self::PEPPOL );
		}
		$order->update_meta_data( self::STATUS, $result['status'] );
		return $result;
	}

	/**
	 * Adds an order note when the VAT number did not check out.
	 *
	 * @param \WC_Order $order The saved order.
	 * @return void
	 */
	public static function note_if_invalid( \WC_Order $order ): void {
		if ( Validator::INVALID !== $order->get_meta( self::STATUS ) ) {
			return;
		}
		$order->add_order_note(
			sprintf(
				/* translators: %s: VAT number */
				__( 'FirmAtlas: the VAT number %s did not check out (KBO/VIES). Check it before invoicing without VAT.', 'firmatlas-for-woocommerce' ),
				(string) $order->get_meta( self::VAT )
			)
		);
	}

	/**
	 * Shows the VAT number, enterprise number and check result under the billing address.
	 *
	 * @param \WC_Order $order The order.
	 * @return void
	 */
	public static function show( $order ): void {
		$vat = (string) $order->get_meta( self::VAT );
		if ( '' === $vat ) {
			return;
		}
		$labels     = array(
			Validator::VALID   => __( 'valid', 'firmatlas-for-woocommerce' ),
			Validator::INVALID => __( 'not valid', 'firmatlas-for-woocommerce' ),
			Validator::UNKNOWN => __( 'not checked', 'firmatlas-for-woocommerce' ),
			Validator::FOREIGN => __( 'not checked (outside Belgium)', 'firmatlas-for-woocommerce' ),
		);
		$status     = (string) $order->get_meta( self::STATUS );
		$enterprise = (string) $order->get_meta( self::ENTERPRISE );
		echo '<p><strong>' . esc_html__( 'VAT number', 'firmatlas-for-woocommerce' ) . ':</strong> ' . esc_html( $vat );
		if ( isset( $labels[ $status ] ) ) {
			echo ' <em>(' . esc_html( $labels[ $status ] ) . ')</em>';
		}
		if ( '' !== $enterprise ) {
			echo '<br><strong>' . esc_html__( 'Enterprise number', 'firmatlas-for-woocommerce' ) . ':</strong> ';
			echo '<a href="' . esc_url( 'https://firmatlas.be/nl/' . $enterprise ) . '" target="_blank" rel="noopener">' . esc_html( Vat::formatted( $enterprise ) ) . '</a>';
			$peppol = array(
				Validator::PEPPOL_YES => __( 'receives Peppol invoices', 'firmatlas-for-woocommerce' ),
				Validator::PEPPOL_NO  => __( 'not on Peppol', 'firmatlas-for-woocommerce' ),
			)[ (string) $order->get_meta( self::PEPPOL ) ] ?? null;
			if ( null !== $peppol ) {
				echo '<br><strong>' . esc_html__( 'Peppol', 'firmatlas-for-woocommerce' ) . ':</strong> ' . esc_html( $peppol );
			}
		}
		echo '</p>';
	}
}
