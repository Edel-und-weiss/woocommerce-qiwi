<?php
/*
Plugin Name: Visa QIWI Wallet for WooCommerce
Plugin URI:
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

	add_filter( 'woocommerce_general_settings', 'add_qiwi_shop_password' );
	function add_qiwi_shop_password($settings) {
	  $updated_settings = array();
	  foreach ($settings as $section) {
	    if (isset( $section['id']) && 'general_options' == $section['id'] &&
	       isset($section['type']) && 'sectionend' == $section['type'] ) {
	    	$updated_settings[] = array(
		        'name'     => __('Visa QIWI Wallet shop_password','qiwi'),
		        'id'       => 'qiwi_shop_password',
		        'type'     => 'text',
		        'css'      => 'min-width:300px;',
		        'std'      => '',  // WC < 2.0
		        'default'  => '',  // WC >= 2.0
		        'desc'     => __( '<br/>shop_password выдаётся при генерации аутентификационных данных в разделе "Данные магазина"', 'qiwi' ),
	      );
		  
			$pages = get_pages(); 
			$p_arr = array();
			foreach ( $pages as $page ) 
				$p_arr[$page->ID] = $page->post_title;
			
			$updated_settings[] = array(
				'name'     => __('Visa QIWI Wallet Страница успешной оплаты','qiwi'),
				'id'       => 'qiwi_success_pay',
				'type'     => 'select',
				'options'  => $p_arr,
				'css'      => 'min-width:300px;',
				'std'      => '',  // WC < 2.0
				'default'  => '',  // WC >= 2.0
				'desc'     => __( 'Страница перехода при успешной оплаты (successURL)', 'qiwi' ),
			  );
		  
		  	$updated_settings[] = array(
				'name'     => __('Visa QIWI Wallet Страница ошибки оплаты','qiwi'),
				'id'       => 'qiwi_fail_pay',
				'type'     => 'select',
				'options'  => $p_arr,
				'css'      => 'min-width:300px;',
				'std'      => '',  // WC < 2.0
				'default'  => '',  // WC >= 2.0
				'desc'     => __( 'Страница перехода при ошибки оплаты (failURL)', 'qiwi' ),
			  );
		
		
	    }
	    $updated_settings[] = $section;
	  }
	  return $updated_settings;
	}



	add_action( 'parse_request', 'qiwi_check_payment' );

	function qiwi_check_payment()
	{
		global $wpdb;
		if ($_REQUEST['qiwi'] == 'check') {
			//die('1');
			$hash = md5($_POST['action'].';'.$_POST['orderSumAmount'].';'.$_POST['orderSumCurrencyPaycash'].';'.
						$_POST['orderSumBankPaycash'].';'.$_POST['shopId'].';'.$_POST['invoiceId'].';'.
						$_POST['customerNumber'].';'.get_option('qiwi_shop_password'));
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