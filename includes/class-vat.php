<?php
/**
 * Belgian VAT and enterprise numbers.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Pure helpers for Belgian VAT numbers. A Belgian VAT number is BE plus the
 * 10-digit enterprise number, whose last two digits are 97 minus the first
 * eight modulo 97.
 */
final class Vat {

	/**
	 * Uppercases a VAT number and removes spaces, dots, dashes and slashes.
	 *
	 * @param string $raw What the customer typed.
	 * @return string
	 */
	public static function normalize( string $raw ): string {
		return strtoupper( (string) preg_replace( '/[\s.\-\/]+/', '', $raw ) );
	}

	/**
	 * Returns the 10 digits of a Belgian VAT or enterprise number, or null when
	 * the input is not shaped like one. Old 9-digit numbers get their leading 0.
	 *
	 * @param string $raw A VAT number (`BE0403.170.701`) or enterprise number.
	 * @return string|null
	 */
	public static function belgian_digits( string $raw ): ?string {
		$n = self::normalize( $raw );
		if ( str_starts_with( $n, 'BE' ) ) {
			$n = substr( $n, 2 );
		}
		if ( ! preg_match( '/^\d{9,10}$/', $n ) ) {
			return null;
		}
		return str_pad( $n, 10, '0', STR_PAD_LEFT );
	}

	/**
	 * Reports whether the input names another country's VAT number (two
	 * letters other than BE first), which this plugin does not check.
	 *
	 * @param string $raw What the customer typed.
	 * @return bool
	 */
	public static function is_foreign( string $raw ): bool {
		$n = self::normalize( $raw );
		return (bool) preg_match( '/^[A-Z]{2}/', $n ) && ! str_starts_with( $n, 'BE' );
	}

	/**
	 * Checks the mod-97 check digits of a 10-digit enterprise number, which
	 * starts with 0 or 1.
	 *
	 * @param string $digits Ten digits.
	 * @return bool
	 */
	public static function checksum_ok( string $digits ): bool {
		if ( ! preg_match( '/^[01]\d{9}$/', $digits ) ) {
			return false;
		}
		$base  = (int) substr( $digits, 0, 8 );
		$check = (int) substr( $digits, 8, 2 );
		return 97 - ( $base % 97 ) === $check;
	}

	/**
	 * Returns the VAT number for 10 digits: `BE0403170701`.
	 *
	 * @param string $digits Ten digits.
	 * @return string
	 */
	public static function vat_number( string $digits ): string {
		return 'BE' . $digits;
	}

	/**
	 * Returns the dotted enterprise number for 10 digits: `0403.170.701`.
	 *
	 * @param string $digits Ten digits.
	 * @return string
	 */
	public static function formatted( string $digits ): string {
		return substr( $digits, 0, 4 ) . '.' . substr( $digits, 4, 3 ) . '.' . substr( $digits, 7, 3 );
	}
}
