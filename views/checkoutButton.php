<?php

$instant_button_style = "";
$instant_button_style_creds = get_option("instant_checkout_button_style");


if ($instant_button_style_creds) {
    if ($instantCheckoutCurrentPage == "cart") {
        if (array_key_exists("cart", $instant_button_style_creds)) {
            $instant_button_style = $instant_button_style_creds["cart"];
        } else {
            $instant_button_style = INSTANT_BUTTON_CLASSES;
        }
    } else if ($instantCheckoutCurrentPage == "checkout") {
        if (array_key_exists("checkout", $instant_button_style_creds)) {
            $instant_button_style = $instant_button_style_creds["checkout"];
        } else {
            $instant_button_style = INSTANT_BUTTON_CLASSES;
        }
    }
} else {
    $instant_button_style = INSTANT_BUTTON_CLASSES;
}

?>

<a class="<?php echo esc_attr($instant_button_style); ?>" onclick="initiateInstantCheckout()">
    &#x26A1; Instant Checkout
</a>
<br />
<script type="text/javascript">
    const INSTANT_CHECKOUT_NO_REDIRECT = false;
    const INSTANT_PURCHASE_MODE = false;
</script>

<?php
include(__DIR__ . '/checkoutTemplate.php');
include(__DIR__ . '/scripts/checkoutButton.php');
