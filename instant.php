<?php
/*
    Plugin Name: Instant Checkout
    Plugin URI: https://getinstant.co
    description: Microcheckout for WooCommerce
    Version: 3.1.0
    Author: Instant Checkout Services Inc.
    Text Domain: instant-plugin
    Domain Path: /languages
    License: GPL2
*/

/**
 * Make sure we don't expose any info if called directly
 */
if ( !function_exists( 'add_action' ) ) {
	echo 'Cannot call Instant plugin directly.';
	exit;
}

/**
 * exist if directly accessed 
 */
if (!defined('ABSPATH')) {
	exit;
}

/**
 * define plugin constant
 */
define('INSTANT_PLUGIN_PATH', trailingslashit(plugin_dir_path( __FILE__ )));
define('INSTANT_PLUGIN_URL', trailingslashit(plugins_url( '', __FILE__ )));
define('INSTANT_BUTTON_CLASSES', 'checkout-button button alt wc-forward instantCheckoutBtn');
define('INSTANT_CO_DEFAULT_SETTINGS', array(
    "everywhere_button" => true,
    "override_default_checkout" => false,
));

/**
 * Include admin.php
 */
if(is_admin()){
	require_once INSTANT_PLUGIN_PATH . "admin/admin.php";
}

$wp_site_url = get_site_url();

$configFile = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configFile, true);

$instant_api_base_url = $config["apiBaseUrl"];
$instant_api_popup_url = $config["popupUrl"];
$dev_mode = $config["devMode"];

$phone_mode = false;

if(array_key_exists("additionalFields", $config)) {
    if (array_key_exists("phone", $config["additionalFields"])) {
        $phone_mode = $config["additionalFields"]["phone"];
    }
}

$instant_logo_icon_uri = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTgwMCIgaGVpZ2h0PSIxODAxIiB2aWV3Qm94PSIwIDAgMTgwMCAxODAxIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgo8cGF0aCBkPSJNODg4LjAzNiAxMDk1LjEyTDExNTEuMzMgMTA5Ny42OUw2MzIuMjUyIDE4MDFMODg4LjAzNiAxMDk1LjEyWiIgZmlsbD0iIzA1NUM5RCIvPgo8cGF0aCBkPSJNMzY1IDEwOTQuMDZMNjQzLjcxNiA3MTYuNDE4TDE0MzQgNzE0LjY1NEwxMTUxLjQyIDEwOTcuNkwzNjUgMTA5NC4wNloiIGZpbGw9IiMwRTg2RDQiLz4KPHBhdGggZD0iTTk2MS41NTYgNTU0LjExOUg3NTMuNEwxMTYwLjg5IDBMOTYxLjU1NiA1NTQuMTE5WiIgZmlsbD0iIzY4QkJGQSIvPgo8L3N2Zz4K";

include(__DIR__ . '/settings.php');
include(__DIR__ . '/checkoutButton.php');
include(__DIR__ . '/paymentGateway.php');
include(__DIR__ . '/checkout.php');