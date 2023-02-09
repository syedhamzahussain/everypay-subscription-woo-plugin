<?php
/**
 * Functions File.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eppg_is_woo_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	if ( true == ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ) ) {
		return true;
	}

	return false;
}


function eppg_recurring_order($order, $api_username, $api_key, $mode, $amount,$order_reference ){

	
	$data['api_username']           = $api_username;
	$data['account_name']           = 'EUR3D1';
	$data['amount']                 = $amount;
	$data['order_reference'] 		= $order_reference;
	$data['email']                  = $order->get_billing_email();
	$data['nonce']                  = wp_create_nonce(time());
	$data['timestamp']             = date("Y-m-d") . "T" . date("H:i:s P");
	$data['merchant_ip']            = $_SERVER['REMOTE_ADDR'];
	$data['token_agreement']        = 'unscheduled';

	
	if('TEST' === $mode){
		$url = EPPG_TEST_GATEWAY_ENDPOINT.'/payments/mit';
	}else{
		$url = EPPG_LIVE_GATEWAY_ENDPOINT.'/payments/mit';	
	}
	$ch  = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt( $ch, CURLOPT_USERPWD, "$api_username:$api_key");
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true ); // this should be set to true in production
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$responseData = curl_exec( $ch );
	
	if ( curl_errno( $ch ) ) {
		return curl_error( $ch );
	}

	curl_close( $ch );
	$response = json_decode( $responseData );
	return $response;
}

function eppg_create_order( $order_id, $return_url, $api_username, $api_key, $mode) {

	global $woocommerce;
	$data = [];
	$customer_order = wc_get_order( $order_id );


	$data['api_username']          = $api_username;
	$data['account_name']          = 'EUR3D1';
	$data['amount']                = $customer_order->get_total();
	$data['order_reference'] 	   = $order_id.time();
	$data['currency']              = get_woocommerce_currency();
	$data['email']                 = $customer_order->get_billing_email();
	$data['nonce']                 = wp_create_nonce(time());
	$data['timestamp']             = date("Y-m-d") . "T" . date("H:i:s P"); 
	
	$data['customer_ip']           = $_SERVER['REMOTE_ADDR'];
	$data['locale']                = 'en';
	$data['preferred_country']     = 'EE';
	$data['billing_city']          = $customer_order->get_billing_city();
	$data['billing_country']       = $customer_order->get_billing_country();
	$data['billing_line1']         = $customer_order->get_billing_address_1();
	$data['billing_line2']         = $customer_order->get_billing_address_2();
	$data['billing_postcode']      = $customer_order->get_billing_postcode();
	$data['billing_state']         = $customer_order->get_billing_state();
	
	$data['customer_url']          = $return_url;

	$data['token_agreement']       = 'recurring';
	$data['request_token']         = true;

	if('TEST' === $mode){
		$url = EPPG_TEST_GATEWAY_ENDPOINT.'/payments/oneoff';
	}else{
		$url = EPPG_LIVE_GATEWAY_ENDPOINT.'/payments/oneoff';	
	}
	$ch  = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, "$api_username:$api_key");
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true ); // this should be set to true in production
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$responseData = curl_exec( $ch );
	
	if ( curl_errno( $ch ) ) {
		return curl_error( $ch );
	}

	curl_close( $ch );
	$response = json_decode( $responseData );
	return $response;
}