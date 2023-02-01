<?php
/**
 * fppg loader Class File.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;

}

if ( ! class_exists( 'EPPG_LOADER' ) ) {

	/**
	 * Saw class.
	 */
	class EPPG_LOADER {


		/**
		 * Function Constructor.
		 */
		public function __construct() {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_everypay_gateway' ), 10, 1 );
			add_action( 'plugins_loaded', array( $this, 'includes' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
			add_action( 'woocommerce_thankyou', array( $this, 'mark_payment_complete' ), 10, 1 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'everypay_unset' ) );
			add_action('admin_init',array( $this, 'admin_init' ),99 );
		}

		public function admin_init(){

			// set fixed title and desc
			$fp_settings = get_option('woocommerce_everypay_settings');
			$fp_settings['title'] = 'everypay';
			$fp_settings['description'] = 'everypay desc';

			

			update_option('woocommerce_everypay_settings',$fp_settings);

		}

		public function everypay_unset( $available_gateways ) {
			if ( isset( $available_gateways['everypay'] ) ) {
				$gateway_options = get_option( 'woocommerce_everypay_settings' );
				if ( empty( $gateway_options['ep_api_username'] ) || empty( $gateway_options['ep_api_key'] ) ) {
					unset( $available_gateways['everypay'] );
				}
			}
			return $available_gateways;
		}


		public function mark_payment_complete( $order_id ) {
			global $wp;
			$order = wc_get_order( $order_id );

			if ( $order->needs_payment() ) {
				if ( 'everypay' === $order->get_payment_method() ) {
					$order_reference_id = $order->get_meta('order_reference');
					$note = 'Successfully paid using everypay and order reference id is '.$order_reference_id;
					$order->add_order_note( $note );
					$order->payment_complete();
					WC()->cart->empty_cart();
				}
			}
		}

		/*
		 * This action hook registers our PHP class as a WooCommerce payment gateway.
		 */
		public function add_everypay_gateway( $methods ) {

			if ( ! in_array( 'EPGG_WC', $methods ) ) {
				$methods[] = 'EPGG_WC';
			}

			return $methods;
		}

		public function includes() {
			require_once EPPG_PLUGIN_DIR . '/includes/class-eppg-wc.php';
		}

		public function admin_assets() {
			if ( isset( $_GET['section'] ) && 'everypay' == $_GET['section'] ) {
				wp_enqueue_script( 'eppg-admin-script', EPPG_ASSETS_DIR_URL . '/js/admin.js', array( 'jquery' ), rand() );
			}

			wp_localize_script(
				'eppg-admin-script',
				'eppg_object',
				array(
					'eppg_logo' => EPPG_ASSETS_DIR_URL . '/images/logo.png',
				)
			);
		}

	}

	new EPPG_LOADER();
}
