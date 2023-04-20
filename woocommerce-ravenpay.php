


<?php

/**
 * Plugin Name: WooCommerce Ravenpay Payment Gateway
 * Plugin URI: https://www.getravenbank.com
 * Description: Make Payment via Ravenbank Webpay
 * Author: Ravenbank
 * Author URI: http://www.getravenbank.com/
 * Version: 0.1.0
 * Text Domain: wc-ravenpay
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2023 Ravenpay, LLC. (dev@getravenbank.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least:   3.0.0
 * WC tested up to:        4.0
 *
 * @package   WC_Ravenpay_Gateway
 * @author    Raven bank
 * @category  Admin
 * @copyright Copyright (c) 2023, Raven Bank, LLC. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This ravenpay is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation and appearing in the file LICENSE
 */

 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_RAVENPAY_MAIN_FILE', __FILE__ );

define( 'WC_RAVENPAY_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

define( 'WC_RAVENPAY_VERSION', '0.1.0' );

/**
 * Initialize Rave WooCommerce payment gateway.
 */
function wc_ravenpay_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/includes/class-wc-ravenpay-gateway.php';


	// if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {

	// 	require_once dirname( __FILE__ ) . '/includes/class-tbz-wc-rave-subscription.php';

	// }

	require_once dirname( __FILE__ ) . '/includes/polyfill.php';


	add_filter( 'woocommerce_payment_gateways', 'wc_add_ravenpay_gateway' );

}
add_action( 'plugins_loaded', 'wc_ravenpay_init' );


/**
* Add Settings link to the plugin entry in the plugins menu
**/
function wc_ravenpay_plugin_action_links( $links ) {

    $settings_link = array(
    	'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_ravenpay' ) . '" title="View Settings">Settings</a>'
    );

    return array_merge( $settings_link, $links );

}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_ravenpay_plugin_action_links' );


/**
* Add Rave Gateway to WC
**/
function wc_add_ravenpay_gateway( $methods ) {

	// if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {
	// 	$methods[] = 'wc_ravenpay_Subscription';
	// } else {
	// 	$methods[] = 'wc_ravenpay_Gateway';
	// }

    $methods[] = 'wc_ravenpay_Gateway';

	return $methods;

}


function ravenpay_enqueue_script() {
    // wp_register_script( 'ravenpay-script', plugin_dir_url( __FILE__ ). '/assets/raven.js', array(), '1.0', true );
    wp_register_script( 'ravenpay-triggers', plugin_dir_url( __FILE__ ). '/assets/js/triggers.js', array('jquery'), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'ravenpay_enqueue_script' );

/**
* Display the test mode notice
**/
function wc_ravenpay_testmode_notice(){

	$settings = get_option( 'woocommerce_wc_ravenpay_settings' );

	$test_mode = isset( $settings['testmode'] ) ? $settings['testmode'] : '';

	if ( 'yes' === $test_mode ) {
    ?>
	    <div class="update-nag">
	        Raven Webpay testmode is still enabled, Click <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_ravenpay' ) ?>">here</a> to disable it when you want to start accepting live payment on your site.
	    </div>
    <?php
	}
}

add_action( 'admin_notices', 'wc_ravenpay_testmode_notice' );

function start_raven_session(){
	if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
		if(function_exists('session_status') && session_status() == PHP_SESSION_NONE) {
			session_start(apply_filters( 'cf_geoplugin_php7_session_options', array(
			  'cache_limiter' => 'private_no_expire',
			  'read_and_close' => false
		   )));
		}
	}
	else if (version_compare(PHP_VERSION, '5.4.0', '>=') && version_compare(PHP_VERSION, '7.0.0', '<'))
	{
		if (function_exists('session_status') && session_status() == PHP_SESSION_NONE) {
			session_cache_limiter('private_no_expire');
			session_start();
		}
	}
	else
	{
		if(session_id() == '') {
			if(version_compare(PHP_VERSION, '4.0.0', '>=')){
				session_cache_limiter('private_no_expire');
			}
			session_start();
		}
	}
};