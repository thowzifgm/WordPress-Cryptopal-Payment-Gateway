<?php

class CPG_Init
{

    function __construct()
    {
        $this->load_helpers();

        $this->load_filters();

        $this->load_actions();

        $this->load_shortcodes();
    }


    function load_helpers()
    {
        foreach (glob(__DIR__ . "/Helpers/*.php") as $filename) {
            require $filename;
        }
    }

    function load_filters()
    {
        add_filter('woocommerce_payment_gateways', 'CPG_CryptopalController::add_new_gateway');
    }

    function load_actions()
    {
        //Create Gateway
        add_action('plugins_loaded', 'CPG_CryptopalController::create_cryptopal_gateway', 11);

        //Webhook
        add_action("rest_api_init", "CPG_CryptopalController::cryptopal_notification");
        
        add_action('woocommerce_thankyou', 'CPG_CryptopalController::open_crypto_payment_window', 10, 1);
    }

    function load_shortcodes()
    {
    }
}
