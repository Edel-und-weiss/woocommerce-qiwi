<?php
/*
Plugin Name: Visa QIWI Wallet for the WooCommerce
Plugin URI: https://github.com/Edel-und-weiss/woocommerce-qiwi-plugin
Version: 0.0.1
Author: Denis Bezik
Author URI: denis.bezik@gmail.com
Description: QIWI payment gateway for WooCommerce
*/

/**
 * Prevent Data Leaks: exit if accessed directly
 **/
if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

include_once 'qiwi/qiwi.php';

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	add_action( 'parse_request', 'qiwi_check_payment' );
	function qiwi_check_payment()
	{
		global $wpdb;
		if ($_REQUEST['qiwi'] == 'check') {
			$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.
						$_POST['orderSumBankPaycash'].';'.$_POST['shopId'].';'.$_POST['invoiceId'].';'.
						$_POST['customerNumber'].';'.$shop_psw);
			if (strtolower($hash) != strtolower($_POST['md5'])) {
				$code = 1;
			} else {
				$order = $wpdb->get_row('SELECT * FROM '.$wpdb->prefix.'posts WHERE ID = '.(int)$_POST['customerNumber']);
				$order_summ = get_post_meta($order->ID,'_order_total',true);
				if (!$order) {
					$code = 200;
				} elseif ($order_summ != $_POST['orderSumAmount']) {
					$code = 100;
				} else {
					$code = 0;
					if ($_POST['action'] == 'paymentAviso') {
						$order_w = new WC_Order( $order->ID );
						$order_w->update_status('processing', __( 'Awaiting BACS payment', 'woocommerce' ));
						$order_w->reduce_order_stock();
						
						$code = 0;
						header('Content-Type: application/xml');
						include('payment_xml.php');
						die();
					} else {
						header('Content-Type: application/xml');
						include('check_xml.php');
						die();
					}
				}
			}
			
			die();
			
		}
	}

}