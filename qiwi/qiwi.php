<?php 
function qiwi_gateway_icon( $gateways ) {
  if ( isset( $gateways['visa_qiwi'] ) ) {
    $url = WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) );
    $gateways['visa_qiwi']->icon = $url . '/qiwi_icon.png';
  }
 
  return $gateways;
}
 
add_filter( 'woocommerce_available_payment_gateways', 'qiwi_gateway_icon' );

add_action( 'plugins_loaded', 'woocommerce_qiwi_init', 0 );

function woocommerce_qiwi_init() {
  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;


class WC_Payment_Qiwi extends WC_Payment_Gateway {
  public function __construct() {
    $this -> id = 'visa_qiwi';
    $this -> method_title  = 'Visa QIWI Wallet';
    $this -> has_fields = false;

    $this -> init_form_fields();
    $this -> init_settings();

    $this -> title = $this -> settings['title'];
    $this -> description = $this -> settings['description'];
    $this -> shop_id = $this -> settings['shop_id'];
    $this -> api_id = $this -> settings['api_id'];

    $this -> msg['message'] = "";
    $this -> msg['class'] = "";

    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
    } else {
      add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
    }
    add_action('woocommerce_receipt_visa_qiwi', array(&$this, 'receipt_page'));
  }

  function init_form_fields() {
     $this -> form_fields = array(
        'enabled' => array(
            'title' => __('Включить/Выключить','visa_qiwi'),
            'type' => 'checkbox',
            'label' => __('Активировать модуль оплаты Visa QIWI Wallet','visa_qiwi'),
            'default' => 'no'),
        'title' => array(
            'title' => __('Заголовок','visa_qiwi'),
            'type'=> 'text',
            'description' => __('Название, которое пользователь видит во время оплаты','visa_qiwi'),
            'default' => __('Visa QIWI Wallet','visa_qiwi')),
        'description' => array(
            'title' => __('Описание','visa_qiwi'),
            'type' => 'textarea',
            'description' => __('Описание, которое пользователь видит во время оплаты','visa_qiwi'),
            'default' => __('Оплата через систему Visa QIWI Wallet','visa_qiwi')),
        'shop_id' => array(
            'title' => 'Shop ID',
            'type' => 'text',
            'description' => __( 'Идентификатор магазина (раздел "Протоколы/данные магазина")', 'visa_qiwi' ) ),
        'api_id' => array(
            'title' => 'API ID',
            'type' => 'text',
            'description' => __('Генерируемый идентификатор пользователя(API ID) (раздел "Протоколы/данные магазина")' , 'visa_qiwi' ) )
    );
  }

  public function admin_options() {
      echo '<h3>'.__('Оплата Visa QIWI Wallet','visa_qiwi').'</h3>';
      echo '<h5>'.__( 'Для подключения системы Visa QIWI Wallet нужно зарегистрировать магазин ','visa_qiwi' );
      echo '<a href="https://ishop.qiwi.com/">https://ishop.qiwi.com/</a>';
      echo __( '. <br>После этого Вы сможете сгенерировать API ID и получить идентификатор магазина.','visa_qiwi' ).'</h5>';
      echo '<table class="form-table">';
      // Generate the HTML For the settings form.
      $this -> generate_settings_html();
      echo '</table>';
  }

  /**
   *  There are no payment fields for payu, but we want to show the description if set.
   **/
  function payment_fields() {
      if ($this -> description) echo wpautop(wptexturize($this -> description));
  }

  /**
   * Receipt Page
   **/
  function receipt_page($order) {
      echo $this -> generate_payu_form($order);
  }
  /**
   * Generate payu button link
   **/
  public function generate_payu_form($order_id){

      global $woocommerce;

      $qiwi_host = "https://w.qiwi.com/order/external/create.action";

      $order = new WC_Order($order_id);
      $txnid = $order_id;
      //  update_post_meta(12345,'test_key',$order);
      $result ='';
      $result .= '<form name=ShopForm method="POST" id="submit_visa_qiwi_payment_form" action="'.$qiwi_host.'">'; 
      $result .= '<input type="hidden" name="from" value="'.$this->shop_id.'">';
      $result .= '<input type=hidden name="summ" value="'.$order->order_total.'" size="43">';
      $result .= '<input type=hidden name="currency" value="RUB">';
      $result .= '<input type=hidden name="to" value="79182428504">';

      $result .= '<input type=submit value="Оплатить">';
      $result .='<script type="text/javascript">';
      $result .='jQuery(function(){
        jQuery("body").block(
          {
              message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />Спасибо за Ваш заказ. Перенаправление на страницу оплаты.",
                  overlayCSS:
            {
              background: "#fff",
                  opacity: 0.6
            },
            css: {
              padding:        20,
                textAlign:      "center",
                color:          "#555",
                border:         "3px solid #aaa",
                backgroundColor:"#fff",
                cursor:         "wait",
                lineHeight:"32px"
            }
          });
          });
        ';
    $result .='jQuery(document).ready(function ($){ jQuery("#submit_visa_qiwi_payment_form").submit(); });';
    $result .='</script>';
    $result .='</form>';
    
    return $result;
  }
  /**
   * Process the payment and return the result
   **/
  function process_payment($order_id) {
      $order = new WC_Order($order_id);
    
      return array('result' => 'success', 'redirect' => $order->get_checkout_payment_url( true ));
  }

  
  function showMessage($content) {
      return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
  }

   // get all pages
  function get_pages($title = false, $indent = true) {
      $wp_pages = get_pages('sort_column=menu_order');
      $page_list = array();
      if ($title) $page_list[] = $title;
      foreach ($wp_pages as $page) {
          $prefix = '';
          // show indented child pages?
          if ($indent) {
              $has_parent = $page->post_parent;
              while($has_parent) {
                  $prefix .=  ' - ';
                  $next_page = get_page($has_parent);
                  $has_parent = $next_page->post_parent;
              }
          }
          // add to page list array array
          $page_list[$page->ID] = $prefix . $page->post_title;
      }
      return $page_list;
  }
}
 /**
   * Add the Gateway to WooCommerce
  **/
  function woocommerce_add_qiwi_gateway($methods) {
      $methods[] = 'WC_Payment_Qiwi';
      return $methods;
  }
  add_filter('woocommerce_payment_gateways', 'woocommerce_add_qiwi_gateway' );
}


add_action('woocommerce_api_wc_test', 'check_test');
function check_test() {
    global $woocommerce;

    echo '234234234';
    if ( isset($_GET['ym']) AND $_GET['ym'] == 'result' ) {
      echo '<pre>';
      print_r($_POST);
      echo '</pre>';
    }
}