<?php
/*
 * Plugin Name: Everypay Payment Gateway
 * Description: Provides you everypay Payment Gateway Integration with Woocommerce.
 * Author: SolCoders
 * Author URI: https://SolCoders.com
 * Version: 1.1.1.0
 * Text Domain: EPPG
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EPPG_PLUGIN_DIR', __DIR__ );
define( 'EPPG_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'EPPG_ASSETS_DIR_URL', EPPG_PLUGIN_DIR_URL . 'assets' );
define( 'EPPG_ABSPATH', dirname( __FILE__ ) );
define( 'EPPG_TEST_GATEWAY_ENDPOINT', 'https://igw-demo.every-pay.com/api/v4' );
define( 'EPPG_LIVE_GATEWAY_ENDPOINT', 'https://pay.every-pay.eu/api/v4' );


require_once EPPG_PLUGIN_DIR . '/includes/helpers.php';

/**
 * Check if WooCommerce is activated.
 */
if ( true == eppg_is_woo_active() ) {
	require_once EPPG_PLUGIN_DIR . '/includes/class-eppg-loader.php';
}