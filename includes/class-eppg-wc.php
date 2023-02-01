<?php
/**
 * eppg Wc Class File.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;

}

if ( ! class_exists( 'EPGG_WC' ) ) {

	/**
	 * Saw class.
	 */
	class EPGG_WC extends WC_Payment_Gateway {

		var $ipn_url;


		/**
		 * Function Constructor.
		 */
		public function __construct() {

			global $woocommerce;

			$this->id                 = 'everypay';
			$this->supports = array( 'subscriptions', 'products', 'subscription_cancellation', 'subscription_reactivation' );
			$this->method_title       = __( 'everypay', 'eppg' );
			$this->method_description = __( 'Payment Via everypay', 'eppg' );
			$this->title              = __( 'everypay', 'eppg' );
			$this->has_fields         = true;
			$this->icon               = EPPG_ASSETS_DIR_URL . '/images/logo.jpeg';
			$this->init_form_fields();
			$this->init_settings();
			$this->ipn_url = add_query_arg( 'wc-api', 'EPGG_WC', home_url( '/' ) );

			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}

			if ( is_admin() ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}

		}
	

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable / Disable', 'eppg' ),
					'label'   => __( 'Enable this payment gateway', 'eppg' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				
				'ep_api_username'     => array(
					'title' => __( 'API Username', 'eshopspay' ),
					'type'  => 'text',
				),
				'ep_api_key' => array(
					'title' => __( 'API Key', 'eshopspay' ),
					'type'  => 'password',
				),
				'ep_mode'            => array(
					'title'   => __( 'Mode', 'eshopspay' ),
					'type'    => 'select',
					'options' => array(
						'TEST' => 'TEST',
						'LIVE' => 'LIVE',
					),
					'css'     => 'max-width:20%;',
					'default' => 'TEST',
				),
				
			);
		}

		public function process_payment( $order_id ) {
        	
			$gateway_options = get_option( 'woocommerce_everypay_settings' );
			$api_username = $gateway_options['ep_api_username'];
			$api_key = $gateway_options['ep_api_key'];
			
			global $woocommerce;
			$customer_order = wc_get_order( $order_id );
            
            $url            = eppg_create_order( $order_id,  $this->get_return_url( $customer_order ), $api_username, $api_key, $gateway_options['ep_mode']);
            
			
			if ( isset($url->payment_link) ) {
				if (WC_Subscriptions_Order::order_contains_subscription($order_id)) {
					update_post_meta( $order_id, 'order_reference', $url->order_reference );
					WC_Subscriptions_Manager::activate_subscriptions_for_order($customer_order);
				}
				return array(
					'result'   => 'success',
					'redirect' => $url->payment_link,
				);
			} else {
				wc_add_notice( 'Something Went Wrong.Please Try later.', 'error' );
				return;
			}

		}

		public function process_subscription_payment($amount_to_charge, $order){
			$gateway_options = get_option( 'woocommerce_everypay_settings' );
			$api_username = $gateway_options['ep_api_username'];
			$api_key = $gateway_options['ep_api_key'];

			$url            = eppg_recurring_order( $order, $api_username, $api_key, $gateway_options['ep_mode'], $amount_to_charge);
			if ( $url ) {
				
			} else {
				wc_add_notice( 'Something Went Wrong.Please Try later.', 'error' );
				return;
			}

		}

	}

	new EPGG_WC();
}