<?php
/**
 * Plugin Name:          FirmAtlas for WooCommerce
 * Plugin URI:           https://github.com/FirmAtlas/firmatlas-for-woocommerce
 * Description:          Belgian company search at checkout: customers pick their company and the billing details fill themselves. Adds a VAT number field and checks Belgian VAT numbers. Company data from the KBO/BCE through the Firmatlas API.
 * Version:              0.1.0
 * Requires at least:    6.6
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce
 * Author:               Arne Van Daele
 * Author URI:           https://arnevandaele.com
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          firmatlas-for-woocommerce
 * Domain Path:          /languages
 * WC requires at least: 9.0
 * WC tested up to:      11.2
 *
 * @package FirmAtlas\WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'FIRMATLAS_WC_VERSION', '0.1.0' );
define( 'FIRMATLAS_WC_FILE', __FILE__ );
define( 'FIRMATLAS_WC_DIR', plugin_dir_path( __FILE__ ) );
define( 'FIRMATLAS_WC_URL', plugin_dir_url( __FILE__ ) );

require_once FIRMATLAS_WC_DIR . 'includes/class-vat.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-settings.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-api.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-validator.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-order-meta.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-checkout-classic.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-checkout-blocks.php';
require_once FIRMATLAS_WC_DIR . 'includes/class-plugin.php';

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action( 'plugins_loaded', array( \FirmAtlas\WooCommerce\Plugin::class, 'boot' ) );
