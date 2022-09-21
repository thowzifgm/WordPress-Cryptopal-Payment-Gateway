<?php

class Cryptopal_Gateway_Main extends WC_Payment_Gateway
{
    /**
     * Initialize this class
     *          
     */
    public function __construct()
    {

        $this->id = 'cryptopal_gateway'; // payment gateway plugin ID
        $this->title = "Cryptopal Gateway";
        $this->description = "Cryptopal gateway allows you to pay on crypto-currency";
        $this->has_fields = true;
        $this->method_title = 'Cryptopal Gateway';
        $this->method_description = 'This gateway allows you to make payments with Cryptopal App';

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Declare the form settings for the plugin     *          
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Cryptopal Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),

            'cpg_description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Description of this Cryptopal Gateway',
                'default'     => '',
                'desc_tip'    => true,
            ),

            'cpg_webshop_id' => array(
                'title'       => 'Webshop ID',
                'type'        => 'text',
                'description' => 'The id of the cryptopal webshop',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'cpg_webhook' => array(
                'title'       => 'Webhook',
                'type'        => 'text',
                'description' => 'The URL of the webhook will be https://{domain}/wp-json/cryptopal_gateway/v1/{Webhook}',
                'default'     => '',
                'desc_tip'    => true,
                'placeholder' => 'Ex: MyShop_webhook'
            ),
        );
    }

    /**
     * Process the form settings for the plugin
     *          
     */
    public function process_admin_options()
    {
        $post_data = $this->get_post_data();

        $description = $post_data["woocommerce_cryptopal_gateway_cpg_description"];
        $webshop_id = $post_data["woocommerce_cryptopal_gateway_cpg_webshop_id"];
        $webhook = $post_data["woocommerce_cryptopal_gateway_cpg_webhook"];

        $settings = new WC_Admin_Settings();

        if ($description == "") {
            $settings->add_error('You must enter a "Description"');
        }

        if ($webshop_id == "") {
            $settings->add_error('You must enter a "Webshop ID"');
        }

        if ($webhook == "") {
            $settings->add_error('You must enter a "Webhook"');
        }

        return parent::process_admin_options();
    }

    /**
     *     
     * @param int $order_id number wich identify an unique order     
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $products = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            $name = $product->get_title();
            $price = $product->get_price();
            $amount = $item->get_quantity();

            $product = array("name" => $name, "amount" => $amount, "price" => $price);
            array_push($products, $product);
        }

        $response = Cryptopal_Gateway_Main::get_api_info($products);

        if (!$response) {
            wc_add_notice("Something went wrong trying to pay with cryptopal method", "error");
        } else {
            $url_payment = $response["url"];
            $paymentID = $response["paymentID"];

            session_start();
            $_SESSION['cryptopal_url_payment'] = $url_payment;

            $order->update_meta_data('cryptopal_paymentID', $paymentID);

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status('on-hold', __('Awaiting for Cryptopal online payment...', 'wc-gateway-offline'));

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'message' => "personalizado",
                'redirect'  => $this->get_return_url($order)
            );
        }
    }

    public function get_api_info($items)
    {
        $url = "https://app.teslacrypto.me/cryptopalPayments";

        $payment_gateway_id = 'cryptopal_gateway';

        // Get an instance of the WC_Payment_Gateways object
        $payment_gateways   = WC_Payment_Gateways::instance();

        // Get the desired WC_Payment_Gateway object
        $payment_gateway = $payment_gateways->payment_gateways()[$payment_gateway_id];

        $webshop_id = $payment_gateway->get_option('cpg_webshop_id');
        $currency = get_woocommerce_currency();

        $body = array(
            "items" => $items,
            "webshopID" => $webshop_id,
            "currency" => $currency
        );

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array('Content-Type' => 'application/json'),
                'body' => wp_json_encode($body),
            )
        );

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code == 200) {
            $body = wp_remote_retrieve_body($response);

            $body = json_decode($body, true);

            return $body;
        } else {

            $body = json_decode($body, true);

            return false;
        }
    }
}
