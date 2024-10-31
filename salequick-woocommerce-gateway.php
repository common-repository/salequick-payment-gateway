<?php
/*
 * Plugin Name: SaleQuick Payment Gateway
 * Plugin URI: https://salequick.com
 * Description: SaleQuick, credit card processing app offers mobile credit card processing and payment processing that integrates with the shop's existing management system.
 * Author: Milstead Technologies
 * Author URI: 
 * Version: 1.1.1
*/

add_action( 'plugins_loaded', 'slq_payment_gateway_init', 0 );
function slq_payment_gateway_init() {
    //if condition use to do nothin while WooCommerce is not installed
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	include_once( 'salequick-woocommerce.php' );
	// class add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'slq_add_gateway' );
	function slq_add_gateway( $methods ) {
		$methods[] = 'slq_pAyment_gaTewaY';
		return $methods;
	}
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'slq_payment_gateway_action_links' );
function slq_payment_gateway_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'slq-payment-gateway' ) . '</a>',
	);
	return array_merge( $plugin_links, $links );
}

