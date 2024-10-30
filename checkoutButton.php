<?php
$instantCheckoutButtonJSRendered = false;
$instantCheckoutTemplateRendered = false;
$instantCheckoutAnalyticsJSRendered = false;


$instantCurrentSettings = instant_get_current_settings();
if ($instantCurrentSettings["override_default_checkout"]) {
    function woocommerce_button_proceed_to_checkout() {}
}

function instant_checkout_button_cart_page()
{
    global $instant_api_popup_url, $instant_api_base_url, $dev_mode, $phone_mode, $instantCheckoutButtonJSRendered, $instantCheckoutTemplateRendered, $instantCheckoutAnalyticsJSRendered;
    $instantAPICreds = instant_get_credentials();
    $instantCheckoutCurrentPage = "cart";
    if ($instantAPICreds === false) {
        return;
    }
    include(__DIR__ . '/views/checkoutButton.php');
}

function instant_checkout_button_checkout_page()
{
    global $instant_api_popup_url, $dev_mode, $phone_mode, $instantCheckoutButtonJSRendered, $instantCheckoutTemplateRendered, $instantCheckoutAnalyticsJSRendered;
    $instantAPICreds = instant_get_credentials();
    $instantCheckoutCurrentPage = "checkout";
    if ($instantAPICreds === false) {
        return;
    }
    include(__DIR__ . '/views/checkoutButton.php');
}


function instant_get_credentials()
{
    $creds = get_option("instant_api_credentials");
    if (!$creds) {
        return false;
    }
    if (!array_key_exists("public", $creds) or !array_key_exists("secret", $creds)) {
        return false;
    }

    if ((strlen($creds["public"]) < 4) or (strlen($creds["secret"]) < 4)) {
        return false;
    }

    return $creds;
}

function instant_buy_now_button()
{
    global $instant_api_popup_url, $instant_api_base_url, $dev_mode, $phone_mode, $instantCheckoutButtonJSRendered, $instantCheckoutTemplateRendered, $instantCheckoutAnalyticsJSRendered;
    $instantAPICreds = instant_get_credentials();
    if ($instantAPICreds === false) {
        return;
    }
    include(__DIR__ . '/views/buyNowButton.php');
}

add_action('wp_ajax_instant_checkout_order', 'instant_checkout_order');

add_action('woocommerce_proceed_to_checkout', 'instant_checkout_button_cart_page');

add_action('woocommerce_before_checkout_form', 'instant_checkout_button_checkout_page');
add_action('woocommerce_after_add_to_cart_button', 'instant_buy_now_button');

// Analytics

function instant_analytics($current_page)
{
    global $instant_api_popup_url, $instant_api_base_url, $instantCheckoutAnalyticsJSRendered;
    include(__DIR__ . '/views/scripts/analytics.php');
}

function instant_analytics_cart()
{
    instant_analytics("cart");
}

function instant_analytics_product()
{
    instant_analytics("product");
}

function instant_analytics_checkout()
{
    instant_analytics("checkout");
}

function instant_everywhere_button()
{
    global $instant_api_popup_url, $instant_api_base_url, $dev_mode, $phone_mode, $instantCheckoutButtonJSRendered, $instantCheckoutTemplateRendered, $instantCheckoutAnalyticsJSRendered, $instantCurrentSettings;
    
    if (!$instantCurrentSettings["everywhere_button"]) {
        return false;
    }
    
    $instantAPICreds = instant_get_credentials();
    if ($instantAPICreds === false) {
        return;
    }

    instant_analytics("other");

    include(__DIR__ . '/views/everywhereButton.php');
}

add_action('woocommerce_proceed_to_checkout', 'instant_analytics_cart');
add_action('woocommerce_after_add_to_cart_button', 'instant_analytics_product');
add_action('woocommerce_before_checkout_form', 'instant_analytics_checkout');
add_action('wp_footer', 'instant_everywhere_button');
