<?php
function qiwi_gateway_icon( $gateways )
{
	if ( isset( $gateways['visa_qiwi'] ) ) {
		$url                         = WP_PLUGIN_URL . "/" . dirname( plugin_basename( __FILE__ ) );
		$gateways['visa_qiwi']->icon = $url . '/qiwi_icon.png';
	}
	
	return $gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'qiwi_gateway_icon' );

add_action( 'plugins_loaded', 'woocommerce_qiwi_init', 0 );
function woocommerce_qiwi_init()
{
	if ( !class_exists( 'WC_Payment_Gateway' ) )
		return;
	
	
	class WC_Payment_Qiwi extends WC_Payment_Gateway
	{
		public function __construct()
		{
			$this->id           = 'visa_qiwi';
			$this->method_title = 'Visa QIWI Wallet';
			$this->has_fields   = false;
			
			$this->init_form_fields();
			$this->init_settings();
			
			$this->title         = $this->settings['title'];
			$this->description   = $this->settings['description'];
			$this->shop_id       = $this->settings['shop_id'];
			$this->api_id        = $this->settings['api_id'];
			$this->shop_password = $this->settings['shop_password'];
			$this->order_prefix  = $this->settings['order_prefix'];
			$this->currency      = $this->settings['currency'];
			$this->lifetime      = $this->settings['lifetime'];
			$this->provider_name = $this->settings['provider_name'];
			$this->success_url   = $this->settings['success_url'];
			$this->fail_url      = $this->settings['fail_url'];
			
			$this->msg['message'] = "";
			$this->msg['class']   = "";
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
					 &$this,
					'process_admin_options' 
				) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array(
					 &$this,
					'process_admin_options' 
				) );
			}
			add_action( 'woocommerce_receipt_visa_qiwi', array(
				 &$this,
				'receipt_page' 
			) );
		}
		
		function init_form_fields()
		{
			$this->form_fields = array(
				 'enabled' => array(
					 'title' => __( 'Включить/Выключить', 'visa_qiwi' ),
					'type' => 'checkbox',
					'label' => __( 'Активировать модуль оплаты Visa QIWI Wallet', 'visa_qiwi' ),
					'default' => 'no' 
				),
				'title' => array(
					 'title' => __( 'Заголовок', 'visa_qiwi' ),
					'type' => 'text',
					'description' => __( 'Название, которое пользователь видит во время выбора типа оплаты', 'visa_qiwi' ),
					'default' => __( 'Visa QIWI Wallet', 'visa_qiwi' ) 
				),
				'description' => array(
					 'title' => __( 'Описание', 'visa_qiwi' ),
					'type' => 'textarea',
					'description' => __( 'Описание, которое пользователь видит во время выбора типа оплаты', 'visa_qiwi' ),
					'default' => __( 'Оплата через систему Visa QIWI Wallet', 'visa_qiwi' ) 
				),
				'provider_name' => array(
					 'title' => __( 'Provider name', 'visa_qiwi' ),
					'type' => 'text',
					'description' => __( 'Название провайдера (вашего магазина), которое будет отображено пользователю при получении им счёта', 'visa_qiwi' ) 
				),
				'shop_id' => array(
					 'title' => 'Shop ID',
					'type' => 'text',
					'description' => __( 'Идентификатор магазина (раздел "Протоколы/данные магазина")', 'visa_qiwi' ) 
				),
				'shop_password' => array(
					 'title' => 'Shop password',
					'type' => 'password',
					'description' => __( 'Пароль магазина, выданный при регистрации (раздел "Протоколы/данные магазина")', 'visa_qiwi' ) 
				),
				'api_id' => array(
					 'title' => 'API ID',
					'type' => 'text',
					'description' => __( 'Генерируемый идентификатор пользователя(API ID) (раздел "Протоколы/данные магазина")', 'visa_qiwi' ) 
				),
				'order_prefix' => array(
					 'title' => 'Order prefix',
					'type' => 'text',
					'description' => __( 'Префикс счёта, например: F-OUTLET-123456, где F-OUTLET - префикс, 123456 - ID заказа', 'visa_qiwi' ) 
				),
				'currency' => array(
					 'title' => 'Currency',
					'type' => 'text',
					'description' => __( 'Валюта, в которой выставляется счёт (в формате Alpha-3 ISO 4217)', 'visa_qiwi' ) 
				),
				'lifetime' => array(
					 'title' => 'Lifetime',
					'type' => 'text',
					'description' => __( 'Время действия выставленного счёта в днях, но не более 30 дней', 'visa_qiwi' ) 
				),
				'success_url' => array(
					 'title' => 'Success URL',
					'type' => 'text',
					'description' => __( 'Страница перехода при успешной оплате (successURL)', 'visa_qiwi' ) 
				),
				'fail_url' => array(
					 'title' => 'Fail URL',
					'type' => 'text',
					'description' => __( 'Страница перехода при ошибке оплаты (failURL)', 'visa_qiwi' ) 
				) 
			);
		}
		
		public function admin_options()
		{
			echo '<h3>' . __( 'Оплата Visa QIWI Wallet', 'visa_qiwi' ) . '</h3>';
			echo '<h5>' . __( 'Для подключения системы Visa QIWI Wallet нужно зарегистрировать магазин ', 'visa_qiwi' );
			echo '<a href="https://ishop.qiwi.com/">https://ishop.qiwi.com/</a>';
			echo __( '. <br>После этого Вы сможете сгенерировать API ID и получить идентификатор и пароль магазина.', 'visa_qiwi' ) . '</h5>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			echo '</table>';
		}
		
		function payment_fields()
		{
			if ( $this->description )
				echo wpautop( wptexturize( $this->description ) );
		}
		
		/**
		 * Receipt Page
		 **/
		function receipt_page( $order )
		{
			echo $this->qiwi_expose_account( $order );
		}
		
		/**
		 * Exposing account
		 **/
		public function qiwi_expose_account( $order_id )
		{
			
			global $woocommerce;
			add_option( "shop_password", $this->shop_password, '', 'yes' );
			
			$data = array();
			
			$order = new WC_Order( $order_id );
			
			$date_now = date( DATE_ISO8601 );
			$bill_id  = $this->order_prefix . "-" . $order_id;
			$data     = array(
				 "user" => "tel:+" . $order->billing_phone,
				"amount" => $order->order_total,
				"ccy" => $this->currency,
				"comment" => "Счёт для заказа " . $bill_id,
				"lifetime" => date( DATE_ISO8601, strtotime( $date_now ) + 24 * 3600 * $this->lifetime ),
				"pay_source" => "qw",
				"prv_name" => $this->provider_name 
			);
			
			$shop_id       = $this->shop_id;
			$shop_password = $this->shop_password;
			$api_id        = $this->api_id;
			
			$ch = curl_init( 'https://w.qiwi.com/api/v2/prv/' . $shop_id . '/bills/' . $bill_id );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $ch, CURLOPT_USERPWD, $api_id . ":" . $shop_password );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				 "Accept: application/json" 
			) );
			
			$response = curl_exec( $ch ) or die( curl_error( $ch ) );
			// echo $response;
			// echo curl_error( $ch );
			curl_close( $ch );
			
			$url = 'https://w.qiwi.com/order/external/main.action?shop=' . $shop_id . '&transaction=' . $bill_id . '&successUrl=' . $this->success_url . '&failUrl=' . $this->fail_url . '&qiwi_phone=' . $order->billing_phone;
			// echo '<br><br><b><a href="' . $url . '">Ссылка переадресации для оплаты счета</a></b>';
			wp_redirect( $url, 301 ); 
			exit;
		}
		
		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id )
		{
			$order = new WC_Order( $order_id );
			return array(
				 'result' => 'success',
				'redirect' => $order->get_checkout_payment_url( true ) 
			);
		}
		
		
		function showMessage( $content )
		{
			return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
		}
	}
	/**
	 * Add the Gateway to WooCommerce
	 **/
	function woocommerce_add_qiwi_gateway( $methods )
	{
		$methods[] = 'WC_Payment_Qiwi';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_qiwi_gateway' );
}