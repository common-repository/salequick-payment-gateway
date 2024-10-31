<?php

class slq_pAyment_gaTewaY extends WC_Payment_Gateway {

	function __construct() {

		// global ID
		$this->id = "slq_payment_gateway";

		// Show Title
		$this->method_title = __( "SaleQuick Gateway", 'slq-payment-gateway' );

		// Show Description
		$this->method_description = __( "SaleQuick Payment Gateway Plug-in for WooCommerce", 'slq-payment-gateway' );

		// vertical tab title
		$this->title = __( "SaleQuick ", 'slq-payment-gateway' );


		$this->icon = null;

		$this->has_fields = true;

		// support default form with credit card
		$this->supports = array( 'default_credit_card_form' );

		// setting defines
		$this->init_form_fields();

		// load time variable setting
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// further check of SSL if you want
		
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // Here is the  End __construct()

	// administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'slq-payment-gateway' ),
				'label'		=> __( 'Enable this payment gateway', 'slq-payment-gateway' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'slq-payment-gateway' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'slq-payment-gateway' ),
				'default'	=> __( 'Credit card', 'slq-payment-gateway' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'slq-payment-gateway' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment title of checkout process.', 'slq-payment-gateway' ),
				'default'	=> __( 'Successfully payment through credit card.', 'slq-payment-gateway' ),
				'css'		=> 'max-width:450px;'
			),
			'api_login' => array(
				'title'		=> __( 'SaleQuick Auth Key', 'slq-payment-gateway' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Auth Key provided by SaleQuick when you signed up for an account.', 'slq-payment-gateway' ),
			),
			'trans_key' => array(
				'title'		=> __( 'SaleQuick Merchant Key', 'slq-payment-gateway' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Merchant Key provided by SaleQuick when you signed up for an account.', 'slq-payment-gateway' ),
			),
			'environment' => array(
				'title'		=> __( 'SaleQuick Test Mode', 'slq-payment-gateway' ),
				'label'		=> __( 'Enable Test Mode', 'slq-payment-gateway' ),
				'type'		=> 'checkbox',
				'description' => __( 'This is the test mode of gateway.', 'slq-payment-gateway' ),
				'default'	=> 'no',
			)
		);		
	}
	
	// Response handled for payment gateway
	public function process_payment( $order_id ) {
		global $woocommerce;

		$customer_order = new WC_Order( $order_id );
		
		// checking for transiction
		$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';

		// Decide which URL to post to
		$environment_url = ( "FALSE" == $environment ) 
                               ? 'https://salequick.com/woocommerce/card_payment'
						   : 'https://salequick.com/woocommerce/card_payment_test';

		// This is where the fun stuff begins
		$payload = array(
			// Salequick Credentials and API Info
			"x_tran_key"           	=> $this->trans_key,
			"x_login"              	=> $this->api_login,
			"x_version"            	=> "1.1.1",
			
			// Order total
			"x_amount"             	=> $customer_order->order_total,
			
			// Credit Card Information
			"x_card_num"           	=> str_replace( array(' ', '-' ), '', sanitize_text_field($_POST['slq_payment_gateway-card-number']) ),
			"x_card_code"          	=> sanitize_text_field($_POST['slq_payment_gateway-card-cvc'])  ? sanitize_text_field($_POST['slq_payment_gateway-card-cvc']) : '',
			"x_exp_date"           	=> str_replace( array( '/', ' '), '', sanitize_text_field($_POST['slq_payment_gateway-card-expiry']) ),
			
			"x_type"               	=> 'AUTH_CAPTURE',
			"x_invoice_num"        	=> str_replace( "#", "", $customer_order->get_order_number() ),
			"x_test_request"       	=> $environment,
			"x_delim_char"         	=> '|',
			"x_encap_char"         	=> '',
			"x_delim_data"         	=> "TRUE",
			"x_relay_response"     	=> "FALSE",
			"x_method"             	=> "CC",
			
			// Billing Information
			"x_first_name"         	=> $customer_order->billing_first_name,
			"x_last_name"          	=> $customer_order->billing_last_name,
			"x_address"            	=> $customer_order->billing_address_1,
			"x_city"              	=> $customer_order->billing_city,
			"x_state"              	=> $customer_order->billing_state,
			"x_zip"                	=> $customer_order->billing_postcode,
			"x_country"            	=> $customer_order->billing_country,
			"x_phone"              	=> $customer_order->billing_phone,
			"x_email"              	=> $customer_order->billing_email,
			
			// Shipping Information
			"x_ship_to_first_name" 	=> $customer_order->shipping_first_name,
			"x_ship_to_last_name"  	=> $customer_order->shipping_last_name,
			"x_ship_to_company"    	=> $customer_order->shipping_company,
			"x_ship_to_address"    	=> $customer_order->shipping_address_1,
			"x_ship_to_city"       	=> $customer_order->shipping_city,
			"x_ship_to_country"    	=> $customer_order->shipping_country,
			"x_ship_to_state"      	=> $customer_order->shipping_state,
			"x_ship_to_zip"        	=> $customer_order->shipping_postcode,
			
			// information customer
			"x_cust_id"            	=> $customer_order->user_id,
			"x_customer_ip"        	=> $_SERVER['REMOTE_ADDR'],
			
		);
	
		// Send this payload to Salequick for processing
		$response = wp_remote_post( $environment_url, array(
			'method'    => 'POST',
			'body'      => http_build_query( $payload ),
			'timeout'   => 90,
			'sslverify' => false,
		) );
		

		if ( is_wp_error( $response ) ) 
			throw new Exception( __( 'There is issue for connectin payment gateway. Sorry for the inconvenience.', 'slq-payment-gateway' ) );

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'Salequick\'s Response was not get any data.', 'slq-payment-gateway' ) );
			
		// get body response while get not error
		$response_body = wp_remote_retrieve_body( $response );

		foreach ( preg_split( "/\r?\n/", $response_body ) as $line ) {
			$resp = explode( "|", $line );
		}
		
		// values get
		$r['response_code']             = str_replace('"', '', $resp[0]);
		$r['response_sub_code']         = str_replace('"', '', $resp[1]);
		$r['response_reason_code']      = str_replace('"', '', $resp[2]);
		$r['response_reason_text']      = str_replace('"', '', $resp[3]);


		// 1 or 4 means the transaction was a success
		if ( ( $r['response_code'] == 1 ) || ( $r['response_code'] == 4 ) ) {
			// Payment successful
			$customer_order->add_order_note( __( 'SaleQuick complete payment.', 'slq-payment-gateway' ) );
												 
			// paid order marked
			$customer_order->payment_complete();

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			//transiction fail
			wc_add_notice( $r['response_reason_text'], 'error' );
			$customer_order->add_order_note( 'Error: '. $r['response_reason_text'] );
		}

	}
	
	// Validate fields
	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				esc_html("<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>");	
			}
		}		
	}




	}