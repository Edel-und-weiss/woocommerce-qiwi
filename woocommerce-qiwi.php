<?php
/*
Plugin Name: Visa QIWI Wallet for the WooCommerce
Plugin URI: https://github.com/Edel-und-weiss/woocommerce-qiwi-plugin
Version: 0.0.3
Author: Denis Bezik
Author URI: denis.bezik@gmail.com
Description: QIWI payment gateway for WooCommerce
*/

/**
 * Prevent Data Leaks: exit if accessed directly
 **/
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !function_exists( 'apache_request_headers' ) ) {
	function apache_request_headers()
	{
		foreach ( $_SERVER as $header_name => $header_value ) {
			if ( substr( $header_name, 0, 5 ) == "HTTP_" ) {
				$header_name          = str_replace( " ", "-", ucwords( strtolower( str_replace( "_", " ", substr( $header_name, 5 ) ) ) ) );
				$result[$header_name] = $header_value;
			}
		}
		return $result;
	}
}

include_once 'qiwi/qiwi.php';

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	add_action( 'parse_request', 'recieve_qiwi_notification' );
	function recieve_qiwi_notification()
	{
		if ( $_REQUEST['qiwi_recieve'] == 'accept' ) {
			
			$headers       = apache_request_headers();
			$params_string = $_POST['amount'] . '|' . $_POST['bill_id'] . '|' . $_POST['ccy'] . '|' . $_POST['command'] . '|' . 
							 $_POST['error'] . '|' . $_POST['prv_name'] . '|' . $_POST['status'] . '|' . $_POST['user'];
			$control_sign  = base64_encode( hash_hmac( 'sha1', $params_string, get_option( 'shop_password' ), true ) );
			$woo_order     = new WC_Order( $order->id );
			
			if ( $control_sign == $headers['X-Api-Signature'] ) {
				if ( $_POST['error'] == 0 ) {
					if ( $_POST['status'] == 'paid' ) {
						$woo_order->payment_complete();
						$woo_order->add_order_note( __( 'Оплата заказа №' . $woo_order->id . ' выполнена. Клиент: ' . $_POST['user'], 'woocommerce' ) );
						header( 'Content-Type: application/xml' );
						$code = 0;
						include( 'result_xml.php' );
						die();
					} elseif ( $_POST['status'] == 'rejected' ) {
						$woo_order->update_status( 'failed', __( 'Счет отклонен клиентом.', 'woocommerce' ) );
						header( 'Content-Type: application/xml' );
						$code = $_POST['error'];
						include( 'result_xml.php' );
						die();
					} elseif ( $_POST['status'] == 'unpaid' ) {
						$woo_order->update_status( 'failed', __( 'Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce' ) );
						header( 'Content-Type: application/xml' );
						$code = $_POST['error'];
						include( 'result_xml.php' );
						die();
					} elseif ( $_POST['status'] == 'expired' ) {
						$woo_order->update_status( 'failed', __( 'Время жизни счета истекло. Счет не оплачен.', 'woocommerce' ) );
						header( 'Content-Type: application/xml' );
						$code = $_POST['error'];
						include( 'result_xml.php' );
						die();
					} else {
						$woo_order->add_order_note( __( 'Оплата заказа №' . $woo_order->id . ' не выполнена. Неизвестный статус заказа.', 'woocommerce' ) );
						header( 'Content-Type: application/xml' );
						$code = $_POST['error'];
						include( 'result_xml.php' );
						die();
					}
				} else {
					// любая другая ошибка
					header( 'Content-Type: application/xml' );
					$code = 300;
					include( 'result_xml.php' );
					die();
				}
			} else {
				// ошибка авторизации
				if ( ( strpos( $_SERVER['REMOTE_ADDR'], "91.232.230." ) == false ) || ( strpos( $_SERVER['REMOTE_ADDR'], "79.142.16." ) == false ) ) {
					header( 'Content-Type: application/xml' );
					$code = 150;
					include( 'result_xml.php' );
					die();
				}
				// ошибка проверки подписи
				header( 'Content-Type: application/xml' );
				$code = 151;
				include( 'result_xml.php' );
				die();
			}
			die();
		}
	}
	
}