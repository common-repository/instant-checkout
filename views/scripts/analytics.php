<?php
if ($instantCheckoutAnalyticsJSRendered) {
    return;
}

$instantCheckoutAnalyticsJSRendered = true;
?>
<script type="text/javascript">
    const INSTANT_CHECKOUT_CURRENT_PAGE = "<?php echo esc_js($current_page); ?>";
    let sessionId = window.sessionStorage.getItem("instant_checkout_session_id");

    // Log function
    async function logEvent(params) {
        if (!sessionId) {
            sessionId = Date.now().toString(16) + "-" + Math.floor(Math.random() * 1000000).toString(16) + "-" + Math.floor(Math.random() * 1000000).toString(16);
            window.sessionStorage.setItem("instant_checkout_session_id", sessionId)
        }

        const logUri = new URL("<?php echo esc_js($instant_api_base_url); ?>/pixel.png");
        if (!params) {
            params = {};
        }
        const docUrl = new URL(document.URL);
        params.referrer = docUrl.hostname;
        params.sessionId = sessionId;
        params.platform = "woo";

        for (const param in params) {
            logUri.searchParams.append(param, params[param]);
        }

        logUri.searchParams.append("rand", Math.floor(Math.random() * 1000000));
        var img = document.createElement("img");
        img.src = logUri.toString();
        img.style = "visibility: hidden";
        document.body.appendChild(img);
    }

    async function addElementEventListeners() {
        document.querySelector("[name='woocommerce_checkout_place_order']")?.addEventListener("click", () => {
            logEvent({
                "action": "placeOrderClick",
                "from": INSTANT_CHECKOUT_CURRENT_PAGE ? INSTANT_CHECKOUT_CURRENT_PAGE : "unavailable",
            });
        });
    }

    window.addEventListener("load", () => {
        logEvent({
            "action": INSTANT_CHECKOUT_CURRENT_PAGE + "PageLoad"
        });
        setTimeout(addElementEventListeners, 500);
    });

</script>