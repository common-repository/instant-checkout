<?php
$INSTANT_CO_DEFAULT_SETTINGS = INSTANT_CO_DEFAULT_SETTINGS;

function instant_admin_set_credentials($data)
{
    if (!is_user_logged_in() or !current_user_can('manage_options')) {
        return array(
            "success" => false,
            "error_code" => "permission_error"
        );
    }

    $json_params = $data->get_json_params();
    if (!array_key_exists("public", $json_params) or !array_key_exists("secret", $json_params)) {
        return array(
            "success" => false,
            "error_code" => "invalid_request"
        );
    }
    // Valid API credentials

    $current_creds = get_option("instant_api_credentials");
    $updated = false;
    if (!$current_creds) {
        add_option("instant_api_credentials", array(
            "public" => $json_params["public"],
            "secret" => $json_params["secret"]
        ));
    } else {
        $updated = true;
        update_option("instant_api_credentials", array(
            "public" => $json_params["public"],
            "secret" => $json_params["secret"]
        ));
    }

    return array(
        "success" => true,
        "updated" => $updated
    );
}

function instant_get_current_settings() {
    $current_settings = INSTANT_CO_DEFAULT_SETTINGS;

    $settings_from_db = get_option("instant_checkout_settings");
    if ($settings_from_db) {
        $current_settings = array_merge($current_settings, $settings_from_db);
    }

    return $current_settings;
}

function instant_admin_set_settings($data)
{
    global $INSTANT_CO_DEFAULT_SETTINGS;
    if (!is_user_logged_in() or !current_user_can('manage_options')) {
        return array(
            "success" => false,
            "error_code" => "permission_error"
        );
    }

    $current_settings = instant_get_current_settings();

    $json_params = $data->get_json_params();
    foreach($json_params as $key => $val) {
        if (!array_key_exists($key, $current_settings)) {
            return array(
                "success" => false,
                "error_code" => "invalid_request"
            );
        }
        $current_settings[$key] = $val;
    }

    $updated = false;
    if (!get_option("instant_checkout_settings")) {
        add_option("instant_checkout_settings", $current_settings);
    } else {
        $updated = true;
        update_option("instant_checkout_settings", $current_settings);
    }

    return array(
        "success" => true,
        "updated" => $updated,
        "settings" => $current_settings
    );
}

add_action('rest_api_init', function () {
    register_rest_route('instantco/v2', '/admin/credentials', array(
        'methods' => 'POST',
        'callback' => 'instant_admin_set_credentials',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('instantco/v2', '/admin/settings', array(
        'methods' => 'POST',
        'callback' => 'instant_admin_set_settings',
        'permission_callback' => '__return_true',
    ));
});


function instant_admin_set_button_style($data)
{

    if (!is_user_logged_in() or !current_user_can('manage_options')) {
        return array(
            "success" => false,
            "error_code" => "permission_error"
        );
    }

    $json_params = $data->get_json_params();
    if (!array_key_exists("checkout", $json_params) or !array_key_exists("product", $json_params) or !array_key_exists("cart", $json_params)) {
        return array(
            "success" => false,
            "error_code" => "invalid_request"
        );
    }

    $current_creds = get_option("instant_checkout_button_style");
    $updated = false;
    if (!$current_creds) {
        add_option("instant_checkout_button_style", array(
            "checkout" => $json_params["checkout"],
            "product" => $json_params["product"],
            "cart" => $json_params["cart"],
        ));
    } else {
        $updated = true;
        update_option("instant_checkout_button_style", array(
            "checkout" => $json_params["checkout"],
            "product" => $json_params["product"],
            "cart" => $json_params["cart"],
        ));
    }

    return array(
        "success" => true,
        "updated" => $updated
    );
}

add_action('rest_api_init', function () {
    register_rest_route('instantco/v2', '/admin/buttonstyle', array(
        'methods' => 'POST',
        'callback' => 'instant_admin_set_button_style',
        'permission_callback' => '__return_true',
    ));
});


function instant_admin_default_button_style($data)
{

    if (!is_user_logged_in() or !current_user_can('manage_options')) {
        return array(
            "success" => false,
            "error_code" => "permission_error"
        );
    }

    $json_params = $data->get_json_params();
    if (!array_key_exists("type", $json_params)) {
        return array(
            "success" => false,
            "error_code" => "invalid_request"
        );
    }

    $current_creds = get_option("instant_checkout_button_style");
    $updated = false;
    if (!$current_creds) {
        add_option("instant_checkout_button_style", array(
            "checkout" => $json_params["checkout"],
            "product" => $json_params["product"],
            "cart" => $json_params["cart"],
            $json_params["type"] => INSTANT_BUTTON_CLASSES,
        ));
    } else {
        $updated = true;
        update_option("instant_checkout_button_style", array(
            "checkout" => $json_params["checkout"],
            "product" => $json_params["product"],
            "cart" => $json_params["cart"],
            $json_params["type"] => INSTANT_BUTTON_CLASSES,
        ));
    }

    return array(
        "success" => true,
        "updated" => $updated
    );
}

add_action('rest_api_init', function () {
    register_rest_route('instantco/v2', '/admin/defaultbuttonstyle', array(
        'methods' => 'POST',
        'callback' => 'instant_admin_default_button_style',
        'permission_callback' => '__return_true',
    ));
});


function instant_setup_page()
{
    global $wp_site_url, $INSTANT_CO_DEFAULT_SETTINGS;
    $wp_nonce = wp_create_nonce('wp_rest');
    wp_localize_script('wp-api', 'wpApiSettings', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => $wp_nonce
    ));

    $instant_public_key = "";
    $instant_secret_key = "";
    $instant_creds = get_option("instant_api_credentials");
    if ($instant_creds and array_key_exists("public", $instant_creds) and array_key_exists("secret", $instant_creds)) {
        $instant_public_key = $instant_creds["public"];
        $instant_secret_key = $instant_creds["secret"];
    }
    $active_plugins = (array) get_option('active_plugins', array());
    $woo_blocks_installed = false;
    foreach ($active_plugins as $key => $value) {
        if (strpos($value, "woocommerce-gutenberg-products-block") !== false) {
            $woo_blocks_installed = true;
            break;
        }
    }

    $instant_current_settings = instant_get_current_settings();
    
    $instant_checkout_page_button_style = "";
    $instant_product_page_button_style = "";
    $instant_cart_page_button_style = "";
    $instant_button_style_creds = get_option("instant_checkout_button_style");
    if ($instant_button_style_creds and array_key_exists("checkout", $instant_button_style_creds) and array_key_exists("product", $instant_button_style_creds) and array_key_exists("cart", $instant_button_style_creds)) {
        $instant_checkout_page_button_style = $instant_button_style_creds["checkout"];
        $instant_product_page_button_style = $instant_button_style_creds["product"];
        $instant_cart_page_button_style = $instant_button_style_creds["cart"];
    }

    include(__DIR__ . '/views/settings.php');
}


function instant_setup_menu_item()
{
    global $instant_logo_icon_uri;
    add_menu_page('Instant Settings:', 'Instant', 'manage_options', 'instant', 'instant_setup_page', $instant_logo_icon_uri);
}

add_action('admin_menu', 'instant_setup_menu_item');
