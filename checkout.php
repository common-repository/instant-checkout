<?php
if (!defined("PHP_FLOAT_EPSILON")) {
    define("PHP_FLOAT_EPSILON", 0.0001);
}

if (!defined("INSTANTCO_CONFIRMED_STATUS")) {
    define("INSTANTCO_CONFIRMED_STATUS", "CONFIRMED");
}

define("INSTANT_HTTP_METHOD_GET", "get");
define("INSTANT_HTTP_METHOD_POST", "post");

function http_post_req($url, $headers, $body, $method)
{
    if ($method === INSTANT_HTTP_METHOD_GET) {
        $args = array(
            'headers'     => $headers,
        );
        $response = wp_remote_retrieve_body(wp_remote_get($url, $args));

        return $response;
    }

    if ($method === INSTANT_HTTP_METHOD_POST) {
        $args = array(
            'body'        => $body,
            'timeout'     => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'cookies'     => array(),
        );
        $response = wp_remote_retrieve_body(wp_remote_post($url, $args));

        return $response;
    }
    return null;
}

function get_checkout_from_instant_api($checkoutId)
{
    global $instant_api_base_url, $dev_mode;
    $apiCreds = get_option("instant_api_credentials");

    $postUrl = $instant_api_base_url . "/v2/checkouts/" . urlencode($checkoutId);
    $headers = array(
        "Content-Type" => "application/json",
        "x-api-key" => $apiCreds["secret"]
    );

    $data = http_post_req($postUrl, $headers, '', INSTANT_HTTP_METHOD_GET);

    try {
        $dataDec = json_decode($data, true);
    } catch (Exception $ex) {
        return null;
    }

    return $dataDec;
}

function confirm_checkout_instant_api($checkoutId)
{
    global $instant_api_base_url, $dev_mode;
    $apiCreds = get_option("instant_api_credentials");


    $postUrl = $instant_api_base_url . "/v2/checkouts/" . urlencode($checkoutId) . ":confirm";
    $headers = array(
        "Content-Type" => "application/json",
        "x-api-key" => $apiCreds["secret"]
    );

    $data = http_post_req($postUrl, $headers, '', INSTANT_HTTP_METHOD_POST);

    try {
        $dataDec = json_decode($data, true);
    } catch (Exception $ex) {
        return null;
    }

    return $dataDec;
}

function calculate_total_money($units, $nanos)
{
    $unitNanos = $nanos / pow(10, 9);
    return floatval(intval($units) + $unitNanos);
}

function instant_purchase_prepare($data)
{
    $productId = $data->get_param('productId');
    if (is_null(WC()->cart)) {
        wc_load_cart();
    }

    $quantity = $data->get_param('quantity');

    WC()->cart->empty_cart(true);
    WC()->session->set('cart', array());

    $variationId = $data->get_param('variationId');

    if ($variationId === NULL) {
        WC()->cart->add_to_cart(intval($productId), intval($quantity));
    } else {
        $query_params = $data->get_query_params();
        $variation = array();
        foreach ($query_params as $key => $value) {
            if (strpos($key, 'attribute_') === 0) {
                $variation[$key] = $value;
            }
        }
        WC()->cart->add_to_cart(intval($productId), intval($quantity), intval($variationId), $variation);
    }

    return array();
}

function instant_start_checkout_session()
{
    if (is_null(WC()->cart)) {
        wc_load_cart();
    }
    WC()->session->set('instant_checkout_session', "true");
    return array(
        "success" => WC()->session->get("instant_checkout_session") === "true"
    );
}

function instant_start_checkout_unset_session()
{
    if (is_null(WC()->cart)) {
        wc_load_cart();
    }
    WC()->session->__unset('instant_checkout_session');
    return array(
        "success" => WC()->session->get("instant_checkout_session") !== "true"
    );
}

function instant_checkout_order($data)
{
    if (is_null(WC()->cart)) {
        wc_load_cart();
    }

    $checkoutId = $data->get_param('checkoutId');

    $orderId = $data->get_param('wcOrderId');

    $crypto = $data->get_param('crypto');

    $order = wc_get_order($orderId);
    if (!$order) {
        return new WP_Error('invalid_woo_order', 'Invalid Order ID', array('status' => 400));
    }

    $checkoutObj = get_checkout_from_instant_api($checkoutId);

    if (!$checkoutObj) {
        return new WP_Error('invalid_instant_checkout', 'Invalid Checkout ID', array('status' => 400));
    }

    $totalWC = floatval($order->get_total());
    $totalInstant = calculate_total_money($checkoutObj["total"]["units"], $checkoutObj["total"]["nanos"]);

    if (abs($totalWC - $totalInstant) > PHP_FLOAT_EPSILON) {
        return new WP_Error('invalid_total', 'Totals do not match ' . $totalWC . " " . $totalInstant, array('status' => 400));
    }

    if ($order->get_currency() !== $checkoutObj["total"]["currencyCode"]) {
        return new WP_Error('invalid_currency', 'Currencies do not match', array('status' => 400));
    }

    if($crypto){
        if($checkoutObj['status'] !== INSTANTCO_CONFIRMED_STATUS){
            return new WP_Error('crypto_not_completed', 'Crypto not received yet', array('status' => 400));
        }
    }else{
        $confirmation = confirm_checkout_instant_api($checkoutId);
        if (!$confirmation || ($confirmation["status"] !== INSTANTCO_CONFIRMED_STATUS)) {
            return new WP_Error('confirm_error', 'Checkout could not be confirmed', array('status' => 500));
        }
    }

    $order->update_status("processing");
    $order->add_meta_data("instantco_checkout_id", $checkoutId);

    $retVal = array(
        "order" => $order,
        "success" => true,
        "redirect_url" => $order->get_checkout_order_received_url(),
        "confirmation" => $confirmation,
    );

    WC()->cart->empty_cart(true);
    WC()->session->set('cart', array());

    return $retVal;
}

add_action('rest_api_init', function () {
    register_rest_route('instantco/v2', '/checkout', array(
        'methods' => 'GET',
        'callback' => 'instant_checkout_order',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('instantco/v2', '/purchase-prepare', array(
        'methods' => 'GET',
        'callback' => 'instant_purchase_prepare',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('instantco/v2', '/start-session', array(
        'methods' => 'GET',
        'callback' => 'instant_start_checkout_session',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('instantco/v2', '/end-session', array(
        'methods' => 'GET',
        'callback' => 'instant_start_checkout_unset_session',
        'permission_callback' => '__return_true',
    ));
});

// function my_custom_rewrite_rules( $rewrite ) {
//     $rewrite->rules['.well-known/apple-developer-merchantid-domain-association'] = plugin_dir_url( __FILE__ ) . "views/files/apple-developer-merchantid-domain-association";
// }
// add_action( 'generate_rewrite_rules', 'my_custom_rewrite_rules' );