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
			
			$this->supports = 
			array( 
				'subscriptions',
				'products',
				'subscription_cancellation',
				'subscription_reactivation',
				'multiple_subscriptions',
				'subscription_date_changes',
				'subscription_suspension'
			);

			$this->method_title       = __( 'everypay', 'eppg' );
			$this->method_description = __( 'Payment Via everypay', 'eppg' );
			$this->title              = __( 'everypay', 'eppg' );
			$this->has_fields         = true;
			$this->icon               = EPPG_ASSETS_DIR_URL . '/images/logo.png';
			$this->init_form_fields();
			$this->init_settings();
			$this->ipn_url = add_query_arg( 'wc-api', 'EPGG_WC', home_url( '/' ) );

			foreach ( $this->settings as $setting_key => $value ) {
				$this->$setting_key = $value;
			}

			if ( is_admin() ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );

		}

		public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
			$this->process_subscription_payment( $amount_to_charge, $renewal_order, true, false );
		}


		public function process_subscription_payment( $amount, $renewal_order, $retry = true, $previous_error = false ) {
			$order_id = $renewal_order->get_id();
			//var_dump(1);die();
			try {
				
				if( 'everypay' === $this->id ){
					$this->eppg_process_subscription_payment( $order_id,$amount );
				}
			}
			catch(\Exception $e){
				$_order = wc_get_order($order_id);
				$_order->add_order_note( $note, 'Error While trying subscription renewal : '.$e->getMessage() );
				$_order->update_status( 'failed' );
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
			
			$order_created            = eppg_create_order( $order_id,  $this->get_return_url( $customer_order ), $api_username, $api_key, $gateway_options['ep_mode']);
			
			
			if ( isset($order_created->payment_link) ) {
				// LOAD THE WC LOGGER
			   $logger = wc_get_logger();
			    
			   // LOG THE FAILED ORDER TO CUSTOM "failed-orders" LOG
			   $logger->info( wc_print_r( $order_created, true ), array( 'source' => 'first-payments' ) );

			   update_post_meta( $order_id, 'order_reference', $order_created->order_reference );

				if (WC_Subscriptions_Order::order_contains_subscription($order_id)) {
					WC_Subscriptions_Manager::activate_subscriptions_for_order($customer_order);
				}
				
				return array(
					'result'   => 'success',
					'redirect' => $order_created->payment_link,
				);
			} else {
				wc_add_notice( 'Something Went Wrong.Please Try later.', 'error' );
				return;
			}

		}

		public function eppg_process_subscription_payment( $order_id,$amount_to_charge ){
			// getting Api settings
			$gateway_options = get_option( 'woocommerce_everypay_settings' );
			$api_username = $gateway_options['ep_api_username'];
			$api_key = $gateway_options['ep_api_key'];

			$renewal_order = wc_get_order( $order_id );
			try{
				$parent_subscription_order_id =  get_post_meta( $order_id,'_subscription_renewal',true);
				$parent_subscription_order = get_post( $parent_subscription_order_id );
				$parent_id = $parent_subscription_order->post_parent;

				$everypay_order_reference = get_post_meta( $parent_id,'order_reference',true);
				if( !empty( $everypay_order_reference ) ){
					$order_completed = eppg_recurring_order( $renewal_order, $api_username, $api_key, $gateway_options['ep_mode'], $amount_to_charge,$everypay_order_reference);
				}

				// LOAD THE WC LOGGER
			   $logger = wc_get_logger();
			    
			   // LOG THE FAILED ORDER TO CUSTOM "failed-orders" LOG
			   $logger->info( wc_print_r( $order_completed, true ), array( 'source' => 'failed-payments' ) );

				if ( isset($order_completed->payment_reference) ) {
					WC_Subscriptions_Manager::process_subscription_payments_on_order( $renewal_order );
					$order_reference_id = $order_completed->order_reference;
					
					$note = 'Subscription payment successfully paid for order reference id :'.$order_reference_id.'. New payment reference id is : '. $order_completed->payment_reference;
					
					$renewal_order->add_order_note( $note );
                    $renewal_order->payment_complete();
					
				} else {
					
					$renewal_order->add_order_note( 'Error While trying subscription renewal' );
					$renewal_order->update_status( 'failed' );
					return;
				}

			}catch(\Exception $e){
				
				$renewal_order->add_order_note( 'Error While trying subscription renewal : '.$e->getMessage() );
				$renewal_order->update_status( 'failed' );
			}
			
			

		}

	}

	new EPGG_WC();
}