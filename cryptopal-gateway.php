<?php

/*
Plugin Name: WordPress Cyptopal Payment Gateway
Plugin URI: https://thowzif.com/
Description: This plugin is a crypto currency payment processor, wich allows you make payments with cryptocurrency.
Version: 0.1
Author: Abdullah Thowzif Hameed
Author URI: https://thowzif.com/
License: GPL2+
*/

register_activation_hook(__FILE__, 'cpg_plugin_activation');

function cpg_plugin_activation()
{
}

register_deactivation_hook(__FILE__, 'cpg_plugin_deactivation');

function cpg_plugin_deactivation()
{
}

require 'plugin_system/Init.php';

require 'controllers/CryptopalController.php';

new CPG_Init();
