<?php
/**
 * Firmatlas API client.
 *
 * @package FirmAtlas\WooCommerce
 */

namespace FirmAtlas\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Calls the Firmatlas API from the server with the secret key.
 */
final class Api {

	/**
	 * Sends a GET request and decodes the JSON answer.
	 *
	 * @param string               $path    Path under the API, e.g. `/v1/meta`.
	 * @param array<string,string> $query   Query parameters.
	 * @param float                $timeout Seconds to wait.
	 * @return array{status:int, body:array<string,mixed>}|\WP_Error
	 */
	public static function get( string $path, array $query = array(), float $timeout = 2.0 ) {
		$url      = add_query_arg( array_map( 'rawurlencode', $query ), Settings::server_api_url() . $path );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
				'headers'     => array(
					'Authorization' => 'Bearer ' . Settings::secret_key(),
					'Accept'        => 'application/json',
					'User-Agent'    => 'FirmAtlas-for-WooCommerce/' . FIRMATLAS_WC_VERSION . '; ' . home_url( '/' ),
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return array(
			'status' => (int) wp_remote_retrieve_response_code( $response ),
			'body'   => is_array( $body ) ? $body : array(),
		);
	}

	/**
	 * Returns the API's error message from an answer, or a fallback.
	 *
	 * @param array{status:int, body:array<string,mixed>} $answer   An answer from get().
	 * @param string                                      $fallback Text when the answer has no message.
	 * @return string
	 */
	public static function error_message( array $answer, string $fallback ): string {
		$error = $answer['body']['error'] ?? null;
		return is_array( $error ) && isset( $error['message'] ) ? (string) $error['message'] : $fallback;
	}
}
