<?php
if ($instantCheckoutButtonJSRendered) {
    return;
}

$instantCheckoutButtonJSRendered = true;
?>
<script type="text/javascript">
    // Checkout Button JS
    const RETRY_THRESHOLD = 2;
    const DEV_MODE = <?php echo $dev_mode ? "true" : "false"; ?>;
    const PHONE_MODE_VAR = <?php echo $phone_mode ? "true" : "false"; ?>;
    const PHONE_MODE = PHONE_MODE_VAR ? "phone" : "";
    let instantApiRedirectUrl = "";
    const INSTANT_APPLE_PAY_SUPPORT = window.ApplePaySession?.canMakePayments() ?? false;
    let INSTANT_FRAME_MODE = false;
    let INSTANT_THIRD_PARTY_COOKIE_SUPPORT = false;

    function consoleLog(...logMsg) {
        if (DEV_MODE)
            console.log(...logMsg);
    }

    var instantPurchaseCartResp = null;
    var instantCheckoutPopup = null;
    var instantStoreApiNonce = "<?php echo esc_js(wp_create_nonce('wp_store_api')); ?>";
    var draftInstantCheckoutOrderId = null;
    var instantCheckoutFrame;

    function createPopupMessage(type, msg, additional) {
        var obj = {
            "type": type,
            "message": msg
        };
        if (additional) {
            obj = Object.assign({}, obj, additional);
        }

        return JSON.stringify(obj);
    }

    function createWCAddress(instantReq) {
        return {
            first_name: instantReq.userProfile.firstName,
            last_name: instantReq.userProfile.lastName,
            address_1: instantReq.shippingAddress.line1,
            address_2: instantReq.shippingAddress.line2,
            city: instantReq.shippingAddress.city,
            state: instantReq.shippingAddress.state,
            country: instantReq.shippingAddress.country,
            postcode: instantReq.shippingAddress.postalCode,
            email: instantReq.email
        }
    }

    function calculateTotal(total) {
        const nanos = Number.parseInt(total.nanos);
        const units = Number.parseInt(total.units);
        const result = units + nanos * Math.pow(10, -9);
        return Number(result).toFixed(2);
    }

    function getHeadersList(headers) {
        var headersMap = {};
        for (var header of headers.entries()) {
            headersMap[header[0]] = header[1];
        }

        return headersMap;
    }

    async function getWooCart() {
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/cart')); ?>", {
            method: 'GET', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json',
                'x-wc-store-api-nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();

        consoleLog("wooCart", resp_body);

        return resp_body;
    }


    function debounce(fn, wait = 0, options = {}) {
        let lastCallAt
        let deferred
        let timer
        let pendingArgs = []
        return function debounced(...args) {
            const currentWait = getWait(wait)
            const currentTime = new Date().getTime()

            const isCold = !lastCallAt || (currentTime - lastCallAt) > currentWait

            lastCallAt = currentTime

            if (isCold && options.leading) {
                return options.accumulate ?
                    Promise.resolve(fn.call(this, [args])).then(result => result[0]) :
                    Promise.resolve(fn.call(this, ...args))
            }

            if (deferred) {
                clearTimeout(timer)
            } else {
                deferred = defer()
            }

            pendingArgs.push(args)
            timer = setTimeout(flush.bind(this), currentWait)

            if (options.accumulate) {
                const argsIndex = pendingArgs.length - 1
                return deferred.promise.then(results => results[argsIndex])
            }

            return deferred.promise
        }

        function flush() {
            const thisDeferred = deferred
            clearTimeout(timer)

            Promise.resolve(
                    options.accumulate ?
                    fn.call(this, pendingArgs) :
                    fn.apply(this, pendingArgs[pendingArgs.length - 1])
                )
                .then(thisDeferred.resolve, thisDeferred.reject)

            pendingArgs = []
            deferred = null
        }
    }

    function getWait(wait) {
        return (typeof wait === 'function') ? wait() : wait
    }

    function defer() {
        const deferred = {}
        deferred.promise = new Promise((resolve, reject) => {
            deferred.resolve = resolve
            deferred.reject = reject
        })
        return deferred
    }

    const debouncedGetWooCart = debounce(getWooCart, 700);

    async function getCartObject() {
        let resp_body = await debouncedGetWooCart();
        return wooCartToOrderObject(resp_body);
    }

    function wooCartToOrderObject(resp_body) {
        consoleLog("WC total price", resp_body.totals.total_price);
        var totalPrice = (Number.parseInt(resp_body.totals.total_price) / 100.00);

        var totalShipping = (Number.parseInt(resp_body.totals.total_shipping) / 100.00);
        var totalTax = (Number.parseInt(resp_body.totals.total_tax) / 100.00);
        var currencyCode = resp_body.totals.currency_code;

        var shippingMethods = [];
        var shippingRatesList = resp_body.shipping_rates;

        // EXT: handle multiple packages
        if (shippingRatesList.length == 1) {
            let shippingMeths = shippingRatesList[0];
            for (var i = 0; i < shippingMeths.shipping_rates.length; i++) {
                let shippingRate = shippingMeths.shipping_rates[i];
                let srPrice = Number.parseInt(shippingRate.price);
                let srTaxes = Number.parseInt(shippingRate.taxes);
                let srTotal = srPrice + srTaxes;
                consoleLog("shipping rate", shippingRate);

                shippingMethods.push({
                    name: shippingRate.name,
                    methodId: shippingRate.method_id,
                    additional: {
                        rateId: shippingRate.rate_id,
                        packageId: shippingMeths.package_id
                    },
                    deliveryTime: shippingRate.delivery_time,
                    price: {
                        currencyCode: shippingRate.currency_code,
                        units: Math.floor(srPrice / 100.0).toString(),
                        nanos: Math.round(((srPrice / 100.0) - Math.floor(srPrice / 100.0)) * Math.pow(10, 9))
                    },
                    taxes: {
                        currencyCode: shippingRate.currency_code,
                        units: Math.floor(srTaxes / 100.0).toString(),
                        nanos: Math.round(((srTaxes / 100.0) - Math.floor(srTaxes / 100.0)) * Math.pow(10, 9))
                    },
                    total: {
                        currencyCode: shippingRate.currency_code,
                        units: Math.floor(srTotal / 100.0).toString(),
                        nanos: Math.round(((srTotal / 100.0) - Math.floor(srTotal / 100.0)) * Math.pow(10, 9))
                    },
                    selected: shippingRate.selected
                });
            }
        }

        let coupons = [];

        for (var cpn of resp_body.coupons) {
            coupons.push({
                couponCode: cpn.code
            });
        }

        return {
            ecommerceOrderId: "notsetyet",
            ecommerceOrderObject: JSON.stringify(resp_body),
            coupons: coupons,
            totalTaxes: {
                currencyCode: currencyCode,
                units: Math.floor(totalTax).toString(),
                nanos: Math.round((totalTax - Math.floor(totalTax)) * Math.pow(10, 9)),
            },
            shippingMethods: shippingMethods,
            totalShipping: {
                currencyCode: currencyCode,
                units: Math.floor(totalShipping).toString(),
                nanos: Math.round((totalShipping - Math.floor(totalShipping)) * Math.pow(10, 9)),
            },
            total: {
                currencyCode: currencyCode,
                units: Math.floor(totalPrice).toString(),
                nanos: Math.round((totalPrice - Math.floor(totalPrice)) * Math.pow(10, 9)),
            },
        }
    }

    async function selectShippingMethod(shippingMethod) {
        const methodId = shippingMethod.methodId;

        const lastCart = await getCartObject();

        const latestShippingMethod = lastCart.shippingMethods.find(x => x.methodId === methodId);

        var data = {
            rate_id: latestShippingMethod.additional.rateId,
            package_id: latestShippingMethod.additional.packageId
        };

        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/cart/select-shipping-rate/')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-WC-Store-API-Nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();
        consoleLog("selectShippingMethod resp", resp_body);

        return resp_body;
    }

    async function updateAddress(address, count) {
        if (!count) {
            count = 0;
        }

        var data = {
            shipping_address: address,
            billing_address: address
        };

        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/cart/update-customer')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-WC-Store-API-Nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();

        if (resp.status !== 200) {
            if (count > RETRY_THRESHOLD) {
                throw resp_body;
            } else {
                return await updateAddress(address, count + 1);
            }
        }

        return resp_body;
    }

    async function setInstantCheckoutSession(start) {
        let sessionUrl = "<?php echo esc_js(get_rest_url(null, 'instantco/v2/end-session')); ?>";
        if (start) {
            sessionUrl = "<?php echo esc_js(get_rest_url(null, 'instantco/v2/start-session')); ?>";
        }

        return await fetch(sessionUrl, {
            method: 'GET', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });
    }

    async function applyCoupon(couponCode) {
        // couponCode: str
        var data = {
            code: couponCode
        };
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/cart/apply-coupon')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-WC-Store-API-Nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();

        if (resp.status !== 200) {
            throw resp_body;
        }

        return resp_body;
    }

    async function removeCoupon(couponCode) {
        // couponCode: str (optional)
        var data = {};
        if (couponCode) {
            data.code = couponCode;
        }

        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/cart/remove-coupon')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-WC-Store-API-Nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();

        return resp_body;
    }

    let createCheckout = async (address, email, phone, count) => {
        if (!count) {
            count = 0;
        }

        if (!address || !email) {
            return;
        }
        var billingAddress = Object.assign({}, address);
        billingAddress["email"] = email;

        if (phone) {
            billingAddress["phone"] = phone;
        }

        let data = {
            shipping_address: address,
            billing_address: billingAddress,
            payment_method: "instant_checkout_gateway"
        };

        await setInstantCheckoutSession(true);
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'wc/store/checkout')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-WC-Store-API-Nonce': instantStoreApiNonce
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });
        await setInstantCheckoutSession(false);

        var headers = getHeadersList(resp.headers);
        if ("x-wc-store-api-nonce" in headers) {
            instantStoreApiNonce = headers["x-wc-store-api-nonce"];
        }
        var resp_body = await resp.json();

        if (!resp_body.status) {
            if (count < RETRY_THRESHOLD) {
                return await createCheckout(address, email, phone, count + 1);
            }
        }

        consoleLog("create checkout object", resp_body);

        return resp_body;
    }

    let confirmCheckout = async (req) => {
        let confirmCheckoutUrl = new URL("<?php echo esc_js(get_rest_url(null, 'instantco/v2/checkout')); ?>");

        confirmCheckoutUrl.searchParams.append("wcOrderId", req.orderId);
        confirmCheckoutUrl.searchParams.append("checkoutId", req.checkoutId);

        if(req.crypto){
            confirmCheckoutUrl.searchParams.append("crypto", true);
        }


        let data = {
            wcOrderId: req.orderId,
            checkoutId: req.checkoutId
        };

        var resp = await fetch(confirmCheckoutUrl, {
            method: 'GET', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        var resp_body = await resp.json();

        if (resp.status !== 200) {
            throw resp_body;
        }

        return resp_body;
    }

    let initiateOrder = async (initiateObj) => {
        consoleLog("initiating order");
        let cart = await getWooCart();
        let shippingAddress = cart.shipping_address;

        if (!shippingAddress || shippingAddress.address_1 == "") {
            throw "no address added";
        }

        if (initiateObj.phone) {
            return await createCheckout(shippingAddress, initiateObj.email, initiateObj.phone);
        }

        return await createCheckout(shippingAddress, initiateObj.email);
    }

    let checkoutSuccessCb = async (checkoutObj) => {
        const input = {
            orderId: checkoutObj.initiation.order_id,
            checkoutId: checkoutObj.checkoutId
        };
        if(checkoutObj.crypto){
            input.crypto = checkoutObj.crypto;
        }
        return await confirmCheckout(input);
    }

    let initiateInstantPurchaseCart = (id) => {
        let purchasePrepareUrl = new URL("<?php echo esc_js(get_rest_url(null, 'instantco/v2/purchase-prepare')); ?>");



        let qty = 1;

        const qtyElem = document.querySelectorAll("form.cart input[name='quantity']");
        if (qtyElem && qtyElem.length > 0) {
            qty = parseInt(qtyElem[0].value);
        }

        const variation = document.querySelectorAll("form.cart input[name='variation_id']");
        if (variation.length) {
            const variation_id = parseInt(variation[0].value, 10);
            if (isNaN(variation_id) || variation_id <= 0) {
                alert("Please choose the relevant options for this product.");
                return false;
            }
            purchasePrepareUrl.searchParams.append("variationId", variation_id);
            const formElem = document.querySelectorAll("form.cart")[0];
            const formD = new FormData(formElem);
            const formVals = Object.fromEntries(formD.entries());
            for (const prop in formVals) {
                purchasePrepareUrl.searchParams.append(prop, formVals[prop]);
            }
        }

        purchasePrepareUrl.searchParams.append("productId", id);
        purchasePrepareUrl.searchParams.append("quantity", qty);

        instantPurchaseCartResp = fetch(purchasePrepareUrl, {
            method: 'GET', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer' // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        });

        return true;
    }

    let initiateInstantPurchase = async (id) => {
        if (!initiateInstantPurchaseCart(id)) {
            return;
        }
        return await initiateInstantCheckout();
    }

    const closeInstantCheckoutFrame = () => {
        instantCheckoutFrame.style.opacity = "0";
        consoleLog("HERE");
        document.body.classList.remove("noscroll");
        setTimeout(() => {
            document.body.removeChild(instantCheckoutFrame);
            instantCheckoutFrame = null;
        }, 500);
    }

    const closeInstantCheckoutPopup = () => {
        if (instantCheckoutPopup) {
            instantCheckoutPopup.postMessage(createPopupMessage("closeInstantPopup", {}), "*");
            instantCheckoutPopup.close();
        }
    }

    const closeInstantCheckout = () => {
        if (INSTANT_FRAME_MODE) {
            closeInstantCheckoutFrame();
        } else {
            closeInstantCheckoutPopup();
        }
    }

    const openInstantCheckoutFrame = () => {
        showInstantCheckoutBackdrop("-loading");
        instantCheckoutFrame = document.createElement('iframe');
        instantCheckoutFrame.src = "<?php echo esc_js($instant_api_popup_url); ?>";
        instantCheckoutFrame.classList.add("instant-checkout-frame");
        instantCheckoutFrame.classList.add("instant-checkout-popup-area");
        instantCheckoutFrame.setAttribute("allowpaymentrequest", "true");
        instantCheckoutFrame.setAttribute("allow", "payment *");
        instantCheckoutFrame.onload = () => {
            // instantCheckoutFrame.style.opacity = "1";
        }
        document.body.classList.add("noscroll");
        document.body.appendChild(instantCheckoutFrame);
    }



    const openThirdPartyCookie = () => {
        const f = document.createElement('iframe');
        f.src = "<?php echo esc_js($instant_api_popup_url); ?>thirdPartyCookie";
        f.setAttribute("allowpaymentrequest", "true");
        f.setAttribute("allow", "payment *");
        f.style.display = "none";

        document.body.appendChild(f);
    }

    const openInstantCheckoutPopup = () => {
        var window_width = 400;
        var window_height = 720;
        var window_left = (window.screen.width / 2) - (window_width / 2);
        var window_top = (window.screen.height / 2) - (window_height / 2);

        var window_params = `scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,status=no,resizable=no,
                            width=${window_width},height=${window_height},top=${window_top},left=${window_left}`;

        showInstantCheckoutBackdrop("-window-focus");
        instantCheckoutPopup = window.open("<?php echo esc_js($instant_api_popup_url); ?>", "Instant", window_params);
        consoleLog("instant checkout popup", instantCheckoutPopup);
        instantCheckoutPopup.focus();
    }

    async function initiateInstantCheckout() {
        if (INSTANT_FRAME_MODE) {
            openInstantCheckoutFrame();
        } else {
            openInstantCheckoutPopup();
            consoleLog("PP21 INSTANT CHECKOUT POPUP", instantCheckoutPopup)
        }
    }

    let hideAllInstantCheckoutBackdrops = () => {
        let backdrops = document.querySelectorAll('[id^="instantco-backdrop-overlay"]');
        for (var backdrop of backdrops) {
            backdrop.style.display = "none";
        }
        document.getElementById("instantco-permanent-backdrop-overlay").style.display = "none";
    }

    let showInstantCheckoutBackdrop = (suffix) => {
        hideAllInstantCheckoutBackdrops();
        var id = "instantco-backdrop-overlay";
        document.getElementById("instantco-permanent-backdrop-overlay").style.display = "flex";
        if (suffix?.length) {
            id += suffix;
        } else {
            return;
        }
        document.getElementById(id).style.display = "flex";
    }
    let hideInstantCheckoutBackdrop = (suffix) => {
        if (!INSTANT_FRAME_MODE) {
            document.getElementById("instantco-permanent-backdrop-overlay").style.display = "none";
        }

        var id = "instantco-backdrop-overlay";
        if (suffix) {
            id += suffix;
        } else {
            return;
        }
        document.getElementById(id).style.display = "none";
    }

    let popupCallback = async (event) => {
        consoleLog("PP21 DATA", event);
        if (INSTANT_FRAME_MODE && !instantCheckoutFrame) {
            consoleLog("PP21 NO CHECKOUT FRAME");
            return;
        } else if (!INSTANT_FRAME_MODE && !instantCheckoutPopup) {
            consoleLog("PP21 NO CHECKOUT POPUP");
            return;
        }

        let popup;
        if (INSTANT_FRAME_MODE) {
            popup = instantCheckoutFrame.contentWindow;
        } else {
            popup = instantCheckoutPopup;
        }

        let data;

        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }

        consoleLog("PP21 LOGGED DATA", data);

        if (data.type) {
            switch (data.type) {
                case "onLoad":
                    logEvent({
                        "action": "popupLoad",
                        "from": INSTANT_CHECKOUT_CURRENT_PAGE ? INSTANT_CHECKOUT_CURRENT_PAGE : "unavailable"
                    });
                    if (INSTANT_FRAME_MODE) {
                        instantCheckoutFrame.style.opacity = "1";
                        setTimeout(showInstantCheckoutBackdrop, 500);
                    }
                    if (INSTANT_PURCHASE_MODE && instantPurchaseCartResp) {
                        await instantPurchaseCartResp;
                        setTimeout(() => {
                            showInstantCheckoutBackdrop("-window-focus")
                        }, 500);
                    }

                    var cartObj;
                    try {
                        cartObj = await getCartObject();
                    } catch (e) {
                        alert("Sorry, could not get order for Instant Checkout.");
                        return;
                    }
                    let instantCheckoutOrderMessage = {
                        type: "ecommerceObject",
                        message: {
                            order: cartObj,
                            siteName: "<?php echo esc_js(get_bloginfo("name")); ?>",
                            siteUrl: "<?php echo esc_js(get_bloginfo("url")); ?>",
                            additionalFields: [PHONE_MODE],
                            apiPublicKey: "<?php echo esc_js($instantAPICreds["public"]); ?>",
                            applePaySupport: INSTANT_APPLE_PAY_SUPPORT,
                            frameMode: INSTANT_FRAME_MODE,
                            showCloseButton: INSTANT_FRAME_MODE,
                        },
                    };
                    popup.postMessage(JSON.stringify(instantCheckoutOrderMessage), "*");
                    break;
                case "onUnload":
                    consoleLog("onunload", data);
                    hideAllInstantCheckoutBackdrops();
                    break;
                case "checkoutComplete":
                    consoleLog("checkout complete");
                    closeInstantCheckout();
                    if (INSTANT_CHECKOUT_NO_REDIRECT) {
                        setTimeout(() => {
                            hideAllInstantCheckoutBackdrops();
                        }, 4000);
                    } else {
                        window.location.href = instantApiRedirectUrl;
                    }
                    break;
                case "checkoutSuccess":
                    let checkoutSuccessMsg;
                    try {
                        showInstantCheckoutBackdrop("-loading");
                        checkoutSuccessMsg = await checkoutSuccessCb(data.message);

                        popup.postMessage(createPopupMessage("checkoutSuccess_return", checkoutSuccessMsg), "*");

                        consoleLog("checkout success msg", checkoutSuccessMsg);
                        instantApiRedirectUrl = checkoutSuccessMsg.redirect_url;
                        logEvent({
                            "action": "checkoutSuccess",
                            "from": INSTANT_CHECKOUT_CURRENT_PAGE ? INSTANT_CHECKOUT_CURRENT_PAGE : "unavailable"
                        });
                        showInstantCheckoutBackdrop("-success");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("checkoutSuccess_error", ex), "*");
                        consoleLog("checkoutSuccessErr", ex);
                        showInstantCheckoutBackdrop("-error");
                        setTimeout(() => {
                            hideAllInstantCheckoutBackdrops();
                        }, 2000);
                    }
                    break;
                case "checkoutInit":
                    let checkoutInitMsg;
                    try {
                        checkoutInitMsg = await initiateOrder(data.message); //data.message is {email: string}
                        consoleLog("initiate order res", checkoutInitMsg);
                        if (!checkoutInitMsg.order_id) {
                            popup.postMessage(createPopupMessage("checkoutInit_error", checkoutInitMsg), "*");
                            return;
                        }
                        popup.postMessage(createPopupMessage("checkoutInit_return", checkoutInitMsg), "*");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("checkoutInit_error", ex), "*");
                    }
                    // EXT: order success handle
                    break;
                case "checkoutCancel":
                    logEvent({
                        "action": "checkoutCancel",
                        "from": INSTANT_CHECKOUT_CURRENT_PAGE ? INSTANT_CHECKOUT_CURRENT_PAGE : "unavailable"
                    });
                    closeInstantCheckout();
                    showInstantCheckoutBackdrop("-cancel");
                    setTimeout(() => {
                        hideAllInstantCheckoutBackdrops();
                    }, 3000);
                case "applyCoupon":
                    let applyCouponMsg;
                    try {
                        applyCouponMsg = await applyCoupon(data.message.couponCode);
                        popup.postMessage(createPopupMessage("applyCoupon_return", applyCouponMsg), "*");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("applyCoupon_error", ex), "*");
                    }
                    break;
                case "removeCoupon":
                    let removeCouponMsg;
                    try {
                        removeCouponMsg = await removeCoupon(data.message.couponCode);
                        popup.postMessage(createPopupMessage("removeCoupon_return", removeCouponMsg), "*");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("removeCoupon_error", ex), "*");
                    }
                    break;
                case "updateAddress":
                    let addressMsg;
                    try {
                        consoleLog("updateAddress: ", data);
                        // Expected format of data.message: {userProfile: {firstName: string, lastName: string}, shippingAddress: {...}}
                        addressMsg = await updateAddress(createWCAddress(data.message));
                        consoleLog("Sending updateAddress", addressMsg);
                        popup.postMessage(createPopupMessage("updateAddress_return", addressMsg), "*");
                    } catch (ex) {
                        consoleLog("Sending updateAddressErr", ex);
                        popup.postMessage(createPopupMessage("updateAddress_error", ex), "*");
                    }
                    break;
                case "selectShippingMethod":
                    let shippingMsg;
                    try {
                        // Expected format of data.message: shippingMethod object
                        shippingMsg = await selectShippingMethod(data.message);
                        popup.postMessage(createPopupMessage("selectShippingMethod_return", shippingMsg), "*");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("selectShippingMethod_error", ex), "*");
                    }
                    break;
                case "getCartObject":
                    let cartObject;
                    try {
                        cartObject = await getCartObject();
                        popup.postMessage(createPopupMessage("getCartObject_return", cartObject), "*");
                    } catch (ex) {
                        popup.postMessage(createPopupMessage("getCartObject_error", ex), "*");
                    }
                    break;
            }
        }
    };

    window.addEventListener(
        "message",
        popupCallback,
        false
    );

    const instantUpdateFrameModeSettings = () => {
        if (instantCheckoutPopup) {
            return;
        }
        const userAgent = navigator.userAgent.toLowerCase();
        let isSafari = false;
        if (userAgent.indexOf('safari') >= 0 && userAgent.indexOf('chrome') < 0) {
            isSafari = true;
        }

        INSTANT_FRAME_MODE = (!isSafari && INSTANT_THIRD_PARTY_COOKIE_SUPPORT);
    }

    const thirdPartyCookieCb = (event) => {
        let data;

        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }
        if (data?.type === "thirdPartyCookieSetting") {
            INSTANT_THIRD_PARTY_COOKIE_SUPPORT = data?.message?.enabled;
            instantUpdateFrameModeSettings();
            window.removeEventListener("message", thirdPartyCookieCb);
        }
    }

    window.addEventListener(
        "message",
        thirdPartyCookieCb,
        false
    );

    let focusOnPopup = () => {
        if (instantCheckoutPopup) {
            instantCheckoutPopup.focus();
        }
    };

    window.addEventListener(
        "focus",
        focusOnPopup,
        false
    );

    window.addEventListener("load", openThirdPartyCookie, true);

    window.addEventListener(
        "beforeunload",
        function() {
            closeInstantCheckout();
        },
        false
    );
</script>