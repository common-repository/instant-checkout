<?php
if ($instantCheckoutTemplateRendered) {
    return;
}

$instantCheckoutTemplateRendered = true;
?>
<style type="text/css">
    .instantCheckoutBtn {
        font-weight: bold;
        text-decoration: none;
        margin: 5px 0;
    }

    .instant-checkout-popup-area {
        width: 400px;
        height: 720px;
        left: 50vw;
        margin-left: -200px;
        position: fixed;
        top: 50vh;
        margin-top: -360px;
        border-radius: 15px;
    }

    .instant-checkout-frame {
        opacity: 0;
        z-index: 99999999999999999999;
        transition: opacity 0.5s;
        background: transparent;
        filter: drop-shadow(0 0 12px black);
    }

    .instantCheckoutBtn img {
        height: 100%;
        width: auto;
        vertical-align: middle;
    }


    div.instant-permanent-backdrop-overlay {
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 9999998;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.3);
        /*display: flex;*/
        display: none;
        position: fixed;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #fff;
    }


    div.instant-window-focus-overlay p {
        max-width: 350px;
    }

    @media screen {
        @media (max-width: 500px),
        (max-height: 750px) {
            iframe.instant-checkout-popup-area {
                border: 0 !important;
            }

            .instant-checkout-popup-area {
                width: 100vw;
                min-height: 100%;
                max-height: 100%;
                height: 100%;
                left: 0;
                position: fixed;
                top: 0;
                border-radius: 0;
                margin: 0;
            }

            .noscroll {
                overflow: hidden !important;
                position: fixed;
                width: 100vw;
                height: 100%;
                top: 0;
                left: 0;
            }

            .noscroll * {
                overflow: hidden !important;
            }
        }
    }

    div.instant-backdrop-overlay {
        z-index: 9999999;
        /*display: flex;*/
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: #fff;
    }

    div.instant-backdrop-overlay img {
        height: 7rem;
        width: auto;
    }

    .lds-ring {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
    }

    .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 64px;
        height: 64px;
        margin: 8px;
        border: 8px solid #ffd400;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #ffd400 transparent transparent transparent;
    }

    .lds-ring div:nth-child(1) {
        animation-delay: -0.45s;
    }

    .lds-ring div:nth-child(2) {
        animation-delay: -0.3s;
    }

    .lds-ring div:nth-child(3) {
        animation-delay: -0.15s;
    }

    @keyframes lds-ring {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<div class="instant-backdrop-overlay instant-checkout-popup-area" id="instantco-backdrop-overlay-loading" onclick="focusOnPopup()">
    <div class="lds-ring">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    </div>
</div>
<div class="instant-permanent-backdrop-overlay" id="instantco-permanent-backdrop-overlay" onclick="focusOnPopup()">
</div>
<div class="instant-backdrop-overlay instant-checkout-popup-area" id="instantco-backdrop-overlay">
    <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/logo.svg" />
</div>
<div class="instant-backdrop-overlay instant-checkout-popup-area" id="instantco-backdrop-overlay-success" onclick="hideAllInstantCheckoutBackdrops()">
    <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/check.svg" /><br />
    Your order was successfully placed
</div>
<div class="instant-backdrop-overlay instant-checkout-popup-area instant-window-focus-overlay" id="instantco-backdrop-overlay-window-focus" onclick="focusOnPopup()">
    <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/logo.svg" /><br />
    <p>Click to view Instant Checkout</p>
</div>
<div class="instant-backdrop-overlay instant-checkout-popup-area" id="instantco-backdrop-overlay-cancel" onclick="hideAllInstantCheckoutBackdrops()">
    <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/cross.svg" /><br />
    Oops, your order was cancelled
</div>
<div class="instant-backdrop-overlay instant-checkout-popup-area" id="instantco-backdrop-overlay-error" onclick="hideAllInstantCheckoutBackdrops()">
    <img src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/images/cross.svg" /><br />
    Oops, there was an error in processing your order
</div>
<br />