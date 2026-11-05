<?php
/**
 * Plugin settings.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the plugin's options, with their defaults.
 */
final class Settings {

	public const API_URL = 'https://api.firmatlas.be';

	public const VALIDATION_OFF   = 'off';
	public const VALIDATION_NOTE  = 'note';
	public const VALIDATION_BLOCK = 'block';

	/**
	 * The publishable key for the browser widget.
	 *
	 * @return string
	 */
	public static function publishable_key(): string {
		return trim( (string) get_option( 'firmatlas_publishable_key', '' ) );
	}

	/**
	 * The secret key for server-side VAT checks.
	 *
	 * @return string
	 */
	public static function secret_key(): string {
		return trim( (string) get_option( 'firmatlas_secret_key', '' ) );
	}

	/**
	 * Whether the company field gets the company search.
	 *
	 * @return bool
	 */
	public static function autocomplete_enabled(): bool {
		return 'yes' === get_option( 'firmatlas_autocomplete', 'yes' ) && '' !== self::publishable_key();
	}

	/**
	 * Whether checkout gets a VAT number field.
	 *
	 * @return bool
	 */
	public static function vat_field_enabled(): bool {
		return 'yes' === get_option( 'firmatlas_vat_field', 'yes' );
	}

	/**
	 * What happens with an invalid Belgian VAT number: off, note (order note only) or block (checkout refuses it).
	 *
	 * @return string
	 */
	public static function validation(): string {
		$mode = (string) get_option( 'firmatlas_vat_validation', self::VALIDATION_NOTE );
		if ( ! in_array( $mode, array( self::VALIDATION_OFF, self::VALIDATION_NOTE, self::VALIDATION_BLOCK ), true ) ) {
			return self::VALIDATION_NOTE;
		}
		if ( '' === self::secret_key() ) {
			return self::VALIDATION_OFF;
		}
		return $mode;
	}

	/**
	 * The widget language: the setting, else the site locale when it is Dutch, French or German, else English.
	 *
	 * @return string
	 */
	public static function language(): string {
		$lang = (string) get_option( 'firmatlas_language', 'auto' );
		if ( in_array( $lang, array( 'nl', 'fr', 'de', 'en' ), true ) ) {
			return $lang;
		}
		$locale = substr( determine_locale(), 0, 2 );
		return in_array( $locale, array( 'nl', 'fr', 'de' ), true ) ? $locale : 'en';
	}

	/**
	 * The API base URL browsers use.
	 *
	 * @return string
	 */
	public static function api_url(): string {
		$url = trim( (string) get_option( 'firmatlas_api_url', '' ) );
		return untrailingslashit( '' !== $url ? $url : self::API_URL );
	}

	/**
	 * The API base URL this server uses; FIRMATLAS_SERVER_API_URL overrides it for development setups.
	 *
	 * @return string
	 */
	public static function server_api_url(): string {
		if ( defined( 'FIRMATLAS_SERVER_API_URL' ) && '' !== FIRMATLAS_SERVER_API_URL ) {
			return untrailingslashit( (string) FIRMATLAS_SERVER_API_URL );
		}
		return self::api_url();
	}
}
