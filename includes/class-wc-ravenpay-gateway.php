<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$trx_ref="";

function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}

function runjs($output, $with_script_tags = true) {
    $js_code = $output;
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}


	/**
	 * Atlas Provided Payment Reference
	 *
	 * @var string
	 */
	// Declare $trx_ref as a global variable
	// global $trx_ref;

/**
 * Class wc_ravenpay_gateway
 */
class WC_Ravenpay_Gateway extends WC_Payment_Gateway
{

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
	
		$this->id = 'ravenpay_gateway';
		$this->icon = 'https://getravenbank.com/static/media/raven-green-logo.eb40fff674ccfe9ff4d866a21a3408e5.svg';
		$this->has_fields = false;
		$this->method_title = __('Raven Webpay', 'wc-gateway-ravenpay');
		$this->method_description = __('Allow your customers pay for goods and services using Raven Pay checkout form, you must have a Raven Atlas Account', 'wc-gateway-ravenpay');

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->instructions = $this->get_option('instructions', $this->description);
		$this->form_style = $this->get_option('payment_form_style');
		$this->enabled = $this->get_option('enabled');
		$this->testmode = 'yes' === $this->get_option('testmode');
		$this->secret_key = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('secret_key');

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		// add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

		// We need custom JavaScript to load the view
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'generate_payment_reference' ), 5, 1 );
		// add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'generate_payment_reference' ) );
		
		// Customer Emails
		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3); 
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'payment_scripts' ), 3, 1 );

		// // Payment listener/API hook.
		// add_action( 'woocommerce_api_tbz_wc_rave_gateway', array( $this, 'verify_rave_transaction' ) );

		// // Webhook listener/API hook.
		// add_action( 'woocommerce_api_tbz_wc_rave_webhook', array( $this, 'process_webhooks' ) );
	}


	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields()
	{

		$this->form_fields = apply_filters(
			'wc_ravenpay_form_fields',
			array(

				'enabled' => array(
					'title' => __('Enable/Disable', 'wc-gateway-ravenpay'),
					'type' => 'checkbox',
					'label' => __('Enable Ravenpay Payment', 'wc-gateway-ravenpay'),
					'default' => 'yes'
				),

				'title' => array(
					'title' => __('Title', 'wc-gateway-ravenpay'),
					'type' => 'text',
					'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-ravenpay'),
					'default' => __('Ravenpay', 'wc-gateway-ravenpay'),
					'desc_tip' => true,
				),

				'description' => array(
					'title' => __('Description', 'wc-gateway-ravenpay'),
					'type' => 'textarea',
					'description' => __('Payment method description that the customer will see on your checkout.', 'wc-gateway-ravenpay'),
					'default' => __('Make payment using Ravenpay.', 'wc-gateway-ravenpay'),
					'desc_tip' => true,
				),

				'instructions' => array(
					'title' => __('Instructions', 'wc-gateway-ravenpay'),
					'type' => 'textarea',
					'description' => __('Instructions that will be added to the thank you page and emails.', 'wc-gateway-ravenpay'),
					'default' => '',
					'desc_tip' => true,
				),

				'testmode' => array(
					'title' => 'Test mode',
					'label' => 'Enable Test Mode',
					'type' => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default' => 'yes',
					'desc_tip' => true,
				),
				'payment_form_style' => array(
					'title' => 'Payment Form Style',
					'type' => 'select',
					'default' => 'Modal',
					'options' => array(
						'inline' => 'Inline',
						'modal' => 'Modal',
						'external' => 'External',
					)
				),
				'test_secret_key' => array(
					'title' => 'Atlas Test Secret Key',
					'type' => 'text'
				),

				'secret_key' => array(
					'title' => 'Atlas Secret Key',
					'type' => 'text'
				),
			)
		);
	}

	function check_cart_updated() {
		// do something when cart is updated
		console_log('cart has been updated');
	}

	
	/**
	 * Generate the payment reference 
	* @param int $order_id
	 * @return array
	 */

	public function generate_payment_reference( $order_id ) 
{
	
	start_raven_session();
	$order = wc_get_order($order_id);
	$_SESSION['order_id'] = $order_id;
	$_SESSION['order_total'] = $order->get_total();
	$_SESSION['merch_ref'] = 'WC-Raven|' . $order_id . '|' . time();
	

	$trx_ref = $_SESSION['trx_ref'];
	$merch_ref = $_SESSION['merch_ref'];
	runjs("localStorage.setItem('trx_ref', '$trx_ref')");
	runjs("localStorage.setItem('trx_ref', '$trx_ref')");

	console_log( $_SESSION['order_id'] );
	console_log( $_SESSION['trx_ref'] );
	console_log( $_SESSION['order_total'] );
	

	// Get the order details
	$order = wc_get_order($order_id);
	$amount = $order->get_total();
	$title = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
	$description = 'Payment for order #' . $order_id;
	$customer_email = $order->get_billing_email();
	$preferred_payment_method = get_option('woocommerce_preferred_payment_method');
	$token = $this->secret_key;

	// Create the request body
	$request_body = array(
		'title' => $title,
		'description' => $description,
		'amount' => $amount,
		'customer_email' => $customer_email,
		'merchant_ref' => $merch_ref,
		'preferred_payment_method' => $preferred_payment_method
	);


	// console_log($_SESSION['order_id'].'The Session'. 'and The Order Id'.  $order_id);

	// Make the API call
	// ($_SESSION['order_id'] != $order_id) || ($amount != $_SESSION['order_total'])
	if ( ($_SESSION['order_id'] != $order_id ) || ($_SESSION['force_refresh'] == true) ){ /*  Make sure reference is only generated once for an order */
		$_SESSION['force_refresh'] = false;
		$_SESSION['merch_ref'] = 'WC-Raven|' . $order_id . '|' . time();
		
		console_log('new payment reference generated - '. time());
		$response = wp_remote_post(
			'https://integrations.getravenbank.com/v1/webpay/create_payment',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer RVSEC-dd6ad10d5eb7905859f19a7a3cb12a429d9e7e88b53827eb6f9e2f06502ed8c7d1b3df7d0aec0c75b99b363702f53740-1674835381115'
				),
				'body' => json_encode($request_body)
			)
		);
	

	// Check for errors
	if (is_wp_error($response)) {
		return false;
	}

	// Parse the response
	$response_body = json_decode(wp_remote_retrieve_body($response), true);

	$_SESSION['trx_ref'] = $response_body['data']['trx_ref'];

	$trx_ref = $_SESSION['trx_ref'];

	//set the trx_ref to local storage
	runjs("localStorage.setItem('trx_ref', '$trx_ref')");
	// Return the payment reference

	// wp_redirect($this->get_return_url( $order ) );
	// exit;
	return $response_body['data']['trx_ref'];

	}
}

   

		
	/**
	 * Output for the order received page.
	 */
	// public function thankyou_page()
	// {
	// 	if ($this->instructions) {
	// 		echo wpautop(wptexturize($this->instructions));
	// 	}
	// }


	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions($order, $sent_to_admin, $plain_text = false)
	{

		if ($this->instructions && !$sent_to_admin && $this->id === $order->payment_method && $order->has_status('on-hold')) {
			echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
		}
	}

	public function payment_scripts( $order_id ) {
		start_raven_session();
		$_SESSION['force_refresh'] = false;

		$order = wc_get_order( $order_id );
		$order_status = $order->get_status();

		console_log($order_status);

		$trx_ref = $_SESSION['trx_ref'];

		$response = wp_remote_get(
			"https://integrations.getravenbank.com/v1/webpay/get_payment?trx_ref=$trx_ref",
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer RVSEC-dd6ad10d5eb7905859f19a7a3cb12a429d9e7e88b53827eb6f9e2f06502ed8c7d1b3df7d0aec0c75b99b363702f53740-1674835381115'
				)
			)
		);

		$response_body = json_decode(wp_remote_retrieve_body($response), true);

		$status = $response_body['data']['status'];
		$amount = $response_body['data']['amount'];

		if ($amount != $order->get_total()){
			$_SESSION['force_refresh'] = true;
		}

		console_log(' refresh val '  . $_SESSION['force_refresh'] . ' '  . $order->get_total() . ' order total ' . ' and amount: ' . $amount);

		$_SESSION['raven_payment_status'] = $status;

		if (get_query_var( 'order-pay' )) {
			
		};

		if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {
			
			if ($status == 'paid') {
				// set force refresh to true to allow new order payment with same order id
				$_SESSION['force_refresh'] = true;

				// Mark as on-hold (we're awaiting the payment)
				$order->payment_complete($trx_ref);

				// add order note 
				$order->add_order_note( sprintf( 'Payment via Raven Webpay was successful (<strong>Transaction Reference:</strong> %s | <strong>Payment Reference:</strong> %s)', $_SESSION['merch_ref'], $_SESSION['trx_ref'] ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				WC()->cart->empty_cart();

				sleep(5);

				// redirect to success page
				wp_redirect($this->get_return_url( $order ));
				exit();
				
			};
		}
		

	}

	/**
	 * Call the payment script
	 *
	 * @param int $order_id
	 * @return array
	 */

	 public function receipt_page( $order_id ) {
		wp_enqueue_script( 'ravenpay-triggers' );

		$order = wc_get_order( $order_id );

		echo '<p>Thank you for your order, please click the button below to pay with Rave.</p>';

		echo '<div> 
		<a class="button alt wc-ravenpay-btn"  >Pay with Raven</a>
		<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">Cancel order &amp; restore cart</a>
		</div>';
	}



	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$token    = WC_Payment_Tokens::get( $token_id );

		if ( isset( $_POST['wc-ravenpay-payment-token'] ) && 'new' !== $_POST['wc-ravenpay-payment-token'] ) {

			$token_id = wc_clean( $_POST['wc-ravenpay-payment-token'] );
			$token    = WC_Payment_Tokens::get( $token_id );


			if ( $token->get_user_id() !== get_current_user_id() ) {

				wc_add_notice( __( 'Invalid token ID', 'wc-ravenpay' ), 'error' );

				return;

			} else {

				$status = $this->process_token_payment( $token->get_token(), $order_id );

				if ( $status ) {

					$order = wc_get_order( $order_id );

					return array(
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					);

				}
			}
		} else {

			// if ( is_user_logged_in() && isset( $_POST['wc-tbz_rave-new-payment-method'] ) && true === (bool) $_POST['wc-tbz_rave-new-payment-method'] && $this->saved_cards ) {

			// 	update_post_meta( $order_id, '_wc_rave_save_card', true );

			// }

			$order = wc_get_order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true ),
			);

		}

	}
} 