<?php
/**
 * VAT number validation.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a VAT number is valid: the check digits first, then the
 * KBO register and the last VIES answer through the Firmatlas API. When the
 * API or VIES cannot answer, the result is "unknown", which never blocks a
 * checkout.
 */
final class Validator {

	public const VALID   = 'valid';
	public const INVALID = 'invalid';
	public const UNKNOWN = 'unknown';
	public const FOREIGN = 'foreign';
	public const EMPTY   = 'empty';

	private const CACHE_SECONDS = DAY_IN_SECONDS;
	// Short, so a customer who typed the number while VIES was slow does not wait again at checkout.
	private const UNKNOWN_CACHE_SECONDS = 10 * MINUTE_IN_SECONDS;

	/**
	 * Results already worked out in this request, by digits.
	 *
	 * @var array<string, array{status:string, message:string, digits:?string}>
	 */
	private static array $checked = array();

	/**
	 * Checks a VAT number.
	 *
	 * @param string $raw What the customer typed.
	 * @return array{status:string, message:string, digits:?string}
	 */
	public static function check( string $raw ): array {
		if ( '' === Vat::normalize( $raw ) ) {
			return self::result( self::EMPTY, '', null );
		}
		if ( Vat::is_foreign( $raw ) ) {
			return self::result( self::FOREIGN, '', null );
		}
		$digits = Vat::belgian_digits( $raw );
		if ( null === $digits || ! Vat::checksum_ok( $digits ) ) {
			return self::result(
				self::INVALID,
				__( 'This is not a valid Belgian VAT number. It looks like BE0123.456.749.', 'firmatlas-for-woocommerce' ),
				$digits
			);
		}
		if ( '' === Settings::secret_key() ) {
			return self::result( self::UNKNOWN, '', $digits );
		}
		if ( isset( self::$checked[ $digits ] ) ) {
			return self::$checked[ $digits ];
		}
		$cached = get_transient( 'firmatlas_vat_' . $digits );
		if ( is_array( $cached ) && isset( $cached['status'], $cached['message'] ) ) {
			return self::result( (string) $cached['status'], (string) $cached['message'], $digits );
		}

		$result                   = self::ask_api( $digits );
		self::$checked[ $digits ] = $result;
		set_transient(
			'firmatlas_vat_' . $digits,
			array(
				'status'  => $result['status'],
				'message' => $result['message'],
			),
			self::UNKNOWN === $result['status'] ? self::UNKNOWN_CACHE_SECONDS : self::CACHE_SECONDS
		);
		return $result;
	}

	/**
	 * Looks the number up; asks for a fresh VIES check when the stored one is missing or stale.
	 *
	 * @param string $digits Ten digits with valid check digits.
	 * @return array{status:string, message:string, digits:?string}
	 */
	private static function ask_api( string $digits ): array {
		$answer = Api::get( '/v1/enterprises/' . $digits );
		if ( is_wp_error( $answer ) ) {
			return self::result( self::UNKNOWN, '', $digits );
		}
		if ( 404 === $answer['status'] ) {
			return self::result(
				self::INVALID,
				__( 'No Belgian company is registered under this VAT number.', 'firmatlas-for-woocommerce' ),
				$digits
			);
		}
		if ( 200 !== $answer['status'] ) {
			return self::result( self::UNKNOWN, '', $digits );
		}
		$vat = self::vies( $answer['body'] );
		if ( null === $vat ) {
			$fresh = Api::get( '/v1/enterprises/' . $digits, array( 'refresh' => 'true' ), 7.0 );
			if ( ! is_wp_error( $fresh ) && 200 === $fresh['status'] ) {
				$vat = self::vies( $fresh['body'] );
			}
		}
		if ( true === $vat ) {
			return self::result( self::VALID, '', $digits );
		}
		if ( false === $vat ) {
			return self::result(
				self::INVALID,
				__( 'This VAT number is not active for intra-EU trade according to VIES.', 'firmatlas-for-woocommerce' ),
				$digits
			);
		}
		return self::result( self::UNKNOWN, '', $digits );
	}

	/**
	 * Reads a clean, recent VIES answer from an enterprise body: true, false, or null when there is none.
	 *
	 * @param array<string,mixed> $body The API's answer.
	 * @return bool|null
	 */
	private static function vies( array $body ): ?bool {
		$vat = $body['data']['vat'] ?? null;
		if ( ! is_array( $vat ) || ! empty( $vat['stale'] ) || ! isset( $vat['valid'] ) || ! is_bool( $vat['valid'] ) ) {
			return null;
		}
		return $vat['valid'];
	}

	/**
	 * Builds a result.
	 *
	 * @param string      $status  One of the status constants.
	 * @param string      $message Text for the customer, empty when there is nothing to say.
	 * @param string|null $digits  The 10 digits when the number is Belgian-shaped.
	 * @return array{status:string, message:string, digits:?string}
	 */
	private static function result( string $status, string $message, ?string $digits ): array {
		return array(
			'status'  => $status,
			'message' => $message,
			'digits'  => $digits,
		);
	}
}
