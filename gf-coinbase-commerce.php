<?php
/*
Plugin Name: Coinbase Commerce Payments For Gravity Forms
Plugin URI: https://commerce.coinbase.com/
Description: Integrates Gravity Forms with Coinbase Commerce Payments. Coinbase Commerce is a service that enables merchants to accept multiple cryptocurrencies directly into a user-controlled wallet.
Version: 1.0.0
Author: Coinbase
Author URI: https://commerce.coinbase.com
License: GPL-2.0+
Text Domain: gf-coinbase-commerce
*/
define('GF_COINBASE_COMMERCE_VERSION', '1.0.0');
define('GF_COINBASE_COMMERCE_SLUG', 'gf-coinbase-commerce');
define('PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PLUGIN_FILE', __FILE__);

add_action('gform_loaded', 'load_gf_coinbase_commerce_plugin', 5);

function load_gf_coinbase_commerce_plugin()
{
    if (!method_exists('GFForms', 'include_addon_framework')) {
        return;
    }

    require_once('class.GFCoinbaseCommercePlugin.php');
    GFAddOn::register('GFCoinbaseCommercePlugin');
    add_action('wp', GFCoinbaseCommercePlugin::process_confirmation(), 5);
}
