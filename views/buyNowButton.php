<?php
global $product;
$product_id = $product->get_id();

$instant_product_page_button_style = "";
$instant_button_style_creds = get_option("instant_checkout_button_style");
if ($instant_button_style_creds and array_key_exists("product", $instant_button_style_creds)) {
    $instant_product_page_button_style = $instant_button_style_creds["product"];
} else {
    $instant_product_page_button_style = INSTANT_BUTTON_CLASSES;
}

?>

<style type="text/css">
    .instantCheckoutBtn {
        font-weight: bold;
        text-decoration: none;
        margin-left: 5px !important;
    }
</style>


<a class="<?php echo esc_attr($instant_product_page_button_style); ?>" id="instant-purchase-button" onclick="initiateInstantPurchase('<?php echo esc_js($product_id); ?>')">
    &#x26A1; Instant Purchase
</a>
<br />
<script type="text/javascript">
    const INSTANT_CHECKOUT_NO_REDIRECT = true;
    const INSTANT_PURCHASE_MODE = true;
</script>


<?php
include(__DIR__ . '/checkoutTemplate.php');
include(__DIR__ . '/scripts/checkoutButton.php');
