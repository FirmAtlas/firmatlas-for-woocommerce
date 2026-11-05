<?php
/**
 * Checks the pure VAT helpers without WordPress: php tests/vat-test.php
 *
 * @package FirmAtlas\WooCommerce
 */

define( 'ABSPATH', __DIR__ );
require __DIR__ . '/../includes/class-vat.php';

use FirmAtlas\WooCommerce\Vat;

$failures = 0;
$check    = static function ( string $label, $got, $want ) use ( &$failures ): void {
	if ( $got !== $want ) {
		++$failures;
		echo "FAIL {$label}: got " . var_export( $got, true ) . ', want ' . var_export( $want, true ) . "\n";
	}
};

$check( 'normalize', Vat::normalize( ' be 0403.170-701 ' ), 'BE0403170701' );
$check( 'digits from VAT', Vat::belgian_digits( 'BE0403.170.701' ), '0403170701' );
$check( 'digits from 9-digit number', Vat::belgian_digits( '403170701' ), '0403170701' );
$check( 'digits refuse letters', Vat::belgian_digits( 'BE04031707XX' ), null );
$check( 'digits refuse short', Vat::belgian_digits( '12345' ), null );
$check( 'checksum Electrabel', Vat::checksum_ok( '0403170701' ), true );
$check( 'checksum Proximus', Vat::checksum_ok( '0202239951' ), true );
$check( 'checksum placeholder', Vat::checksum_ok( '0123456749' ), true );
$check( 'checksum off by one', Vat::checksum_ok( '0403170702' ), false );
$check( 'checksum must start 0 or 1', Vat::checksum_ok( '2403170701' ), false );
$check( 'foreign NL', Vat::is_foreign( 'NL123456789B01' ), true );
$check( 'foreign BE', Vat::is_foreign( 'be0403170701' ), false );
$check( 'foreign digits', Vat::is_foreign( '0403170701' ), false );
$check( 'vat number', Vat::vat_number( '0403170701' ), 'BE0403170701' );
$check( 'formatted', Vat::formatted( '0403170701' ), '0403.170.701' );

echo 0 === $failures ? "ok\n" : "{$failures} failed\n";
exit( 0 === $failures ? 0 : 1 );
