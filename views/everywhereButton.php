<?php
?>

<style type="text/css">
    div.instant-everywhere-button-holder {
        position: fixed;
        bottom: 1.2rem;
        left: 1.2rem;
        height: 2.5rem;
        width: 15rem;
        padding: 0;
        z-index: 9999997;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.7s;
    }

    button.instant-everywhere-button {
        text-align: center;
        width: auto;
        height: 100%;
        line-height: 100%;
        padding: 0 1rem;
        background-color: #00000060 !important;
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        border: 0 !important;
        font-size: 1.2rem;
        border-radius: 0.5rem;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        font-weight: normal;
        transition: all 0.5s;
        color: #ffffff !important;
        gap: 0.3rem;
    }

    button.instant-everywhere-button:hover {
        background-color: #000000FF !important;
        border: 0 !important;
        backdrop-filter: none;
    }

    button.instant-everywhere-button img {
        height: 1.2rem;
        width: auto;
        vertical-align: middle;
        margin: auto;
    }

    button.instant-everywhere-button span {
    }

    #instant-everywhere-button-total {
        font-weight: bold;
    }
</style>

<div class="instant-everywhere-button-holder" id="instant-everywhere-button">
    <button class="instant-everywhere-button" onclick="initiateInstantCheckout()">
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/cart.svg" />
        <span>Checkout</span>
        <span id="instant-everywhere-button-total"></span>
    </button>
</div>

<script type="text/javascript">
    const INSTANT_CHECKOUT_NO_REDIRECT = false;
    const INSTANT_PURCHASE_MODE = false;
</script>


<?php
include(__DIR__ . '/checkoutTemplate.php');
include(__DIR__ . '/scripts/checkoutButton.php');
include(__DIR__ . '/scripts/everywhereButton.php');
