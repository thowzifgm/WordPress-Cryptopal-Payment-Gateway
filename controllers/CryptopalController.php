<?php

class CPG_CryptopalController
{

  /**
   * Create and initialize cryptopal gateway
   *          
   * @return object returns the cryptopal gateway instance
   */
  static function create_cryptopal_gateway()
  {
    require 'class-cryptopal-gateway-main.php';

    $gateway = new Cryptopal_Gateway_Main();

    return $gateway;
  }

  /**
   * Add cryptopal gateway
   *          
   * @return object returns the cryptopal gateway instance
   */
  static function add_new_gateway($gateways)
  {
    $gateways[] = 'Cryptopal_Gateway_Main';
    return $gateways;
  }


  //WEBHOOK
  static function cryptopal_notification()
  {
    $payment_gateway_id = 'cryptopal_gateway';

    // Get an instance of the WC_Payment_Gateways object
    $payment_gateways   = WC_Payment_Gateways::instance();

    // Get the desired WC_Payment_Gateway object
    $payment_gateway = $payment_gateways->payment_gateways()[$payment_gateway_id];

    //$webshop_id = get_option("cpg_webshop_id");
    $webhook_endpoint = $payment_gateway->get_option('cpg_webhook');

    register_rest_route(
      "cryptopal_gateway/v1/", //Namespace
      $webhook_endpoint,    //Endpoint      
      array(
        "methods" => "POST",
        "callback" => "CPG_CryptopalController::cryptopal_gateway_receive_callback"
      )
    );
  }

  static function cryptopal_gateway_receive_callback(WP_REST_Request $req)
  {
    $body = $req->get_body();

    //Change order status
    $body = json_decode($body, true);

    $paymentID = $body["paymentID"];

    $temp = get_posts(array(
      'numberposts' => 1,
      'meta_key'    => 'cryptopal_paymentID',
      'meta_value'  => $paymentID,
      'post_type'   => 'shop_order',
      'post_status' => 'wc-on-hold',
    ));

    if (count($temp) == 1) {
      $order_id = $temp[0]->ID;

      $order = wc_get_order($order_id);
      $order->set_status('wc-completed');
      $order->save();
    }

    return http_response_code(200);
  }
  //WEBHOOK


  //SHOP FUNCTION
  static function open_crypto_payment_window($id_order)
  {
    session_start();
    $url = $_SESSION['cryptopal_url_payment'];

    $order = wc_get_order($id_order);
    $payment_method = $order->get_payment_method();

    if ($payment_method == "cryptopal_gateway") {
      wp_redirect($url);
      exit;
    }

    return '';
  }
}
