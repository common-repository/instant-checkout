<style type="text/css">
    .instant-admin-container {
        margin-top: 60px;
        padding-right: 10px;
    }

    .instant-admin-warning {
        margin-top: 30px;
        height: 40px;
        line-height: 40px;
        padding: 20px 0;
        color: #000;
        background: #ffdddd;
    }

    #instant-admin-container .v-gap {
        margin: 50px 0;
    }

    #instant-admin-container .container {
        margin-top: 15px;
        background-color: #F9FAFB;
        border-radius: 8px;
        box-shadow: 0 4px 16px 5px rgba(0, 0, 0, 0.1),
            0 2px 14px 5px rgba(0, 0, 0, 0.06);
    }

    #instant-admin-container .container-header {
        padding: 15px 30px 10px 30px;
        background-color: #E5E7EB;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    #instant-admin-container .container-body {
        padding: 15px 30px 30px 30px;
    }

    #instant-admin-container .instant-text-input {
        display: block;
        width: 100%;
        border: 0;
        border-bottom: 2px solid #000;
        padding: 5px 22px;
        border-radius: 5px;
        margin-bottom: 10px;
        background-color: white;
        font-size: 16px;
    }

    #instant-admin-container .instant-checkbox-input {
        margin-left: 1rem;
    }

    #instant-admin-container .instant-text-input:active,
    #instant-admin-container .instant-text-input:focus {
        border: 1px solid #000;
        border-bottom: 2px solid #000;
        outline: none !important;
    }

    #instant-admin-container .instant-set-button {
        display: block;
        margin-top: 30px;
        width: 250px;
        text-align: center;
        padding: 5px 25px;
        line-height: 30px;
        border: 2px solid #000;
        font-weight: bold;
        text-decoration: none;
        color: #000;
        border-radius: 8px;
        cursor: pointer;
        background-color: white;
        transition: background-color 0.2s ease-in-out 0s, color 0.2s ease-in-out 0s;
    }

    #instant-admin-container .instant-set-button:hover {
        background-color: #000;
        color: white;
    }

    #instant-admin-container .form-control {
        margin: 10px 0 20px 0;
        width: 100%;
    }

    #instant-admin-container label {
        font-weight: 600;
        font-size: 16px;
        display: inline-block;
        padding: 5px 0 10px 0;
    }

    #instant-admin-container .default-btn {
        display: inline;
        background-color: #4B5563;
        border: none;
        border-radius: 8px;
        color: white;
        padding: 5px 10px;
        text-align: center;
        font-size: 12px;
        cursor: pointer;
        float: right;
    }

    @media only screen and (min-width: 1024px) {
        #instant-admin-container .form-control {
            width: 55%;
        }
    }
</style>
<div class="wrap" id="instant-admin-container">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div class="container">
        <h1 class="container-header">API Keys</h1>
        <form role="form" id="instant-api-keys-form" aria-label="Add instant api keys" class="container-body">
            <div class="form-control">
                <label for="instant-public-key-input">Public Key: </label>
                <input type="text" id="instant-public-key-input" placeholder="Instant API Public Key" class="instant-text-input" value="<?php echo esc_attr($instant_public_key); ?>" />
            </div>
            <div class="form-control">
                <label for="instant-secret-key-input">Secret Key: </label>
                <input type="text" id="instant-secret-key-input" placeholder="Instant API Secret Key" class="instant-text-input" value="<?php echo esc_attr($instant_secret_key); ?>" />
            </div>
            <input type="submit" class="instant-set-button" value="Set API Credentials">
        </form>
    </div>


    <div class="container">
        <h1 class="container-header">Settings</h1>
        <form role="form" id="instant-checkout-settings-form" aria-label="Update Instant Checkout Settings" class="container-body">
            <div class="form-control">
                <label for="instant-everywhere-button-input">Display everywhere button as fixed footer </label>
                <input type="checkbox" id="instant-everywhere-button-input" placeholder="Instant API Public Key" class="instant-checkbox-input" <?php if($instant_current_settings["everywhere_button"]) { echo "checked"; } ?> />
            </div>
            <div class="form-control">
                <label for="instant-override-default-checkout-input">Use Instant Checkout as exclusive checkout method</label>
                <input type="checkbox" id="instant-override-default-checkout-input" class="instant-checkbox-input" <?php if($instant_current_settings["override_default_checkout"]) { echo "checked"; } ?> />
            </div>
            <input type="submit" class="instant-set-button" value="Save">
        </form>
    </div>

    <div class="v-gap"></div>

    <div class="container">
        <h1 class="container-header">Button Styling</h1>
        <form role="form" id="instant-button-style-form" aria-label="Add instant button styles" class="container-body">
            <div class="form-control">
                <label for="instant-checkout-page-button-style-input">Checkout Page Button CSS Classes </label> <button tyep="button" class="default-btn" id="instant-checkout-page-button-style-default">Revert Defaults</button>
                <input type="text" id="instant-checkout-page-button-style-input" placeholder="class-name" class="instant-text-input" value="<?php echo str_replace(" ", ",", esc_attr($instant_checkout_page_button_style)); ?>" />
                <input type="hidden" id="instant-checkout-page-button-class-values" value="<?php echo esc_attr($instant_cart_page_button_style); ?>">
            </div>
            <div class="form-control">
                <label for="instant-product-page-button-style-input">Product Page Button CSS Classes </label> <button tyep="button" class="default-btn" id="instant-product-page-button-style-default">Revert Defaults</button>
                <input type="text" id="instant-product-page-button-style-input" name="instant-product-page-button-style-input" placeholder="class-name" class="instant-text-input" value='<?php echo str_replace(" ", ",", esc_attr($instant_product_page_button_style)); ?>'>
                <input type="hidden" id="instant-product-page-button-class-values" value="<?php echo esc_attr($instant_product_page_button_style); ?>">
            </div>
            <div class="form-control">
                <label for="instant-cart-page-button-style-input">Cart Page Button CSS Classes </label> <button tyep="button" class="default-btn" id="instant-cart-page-button-style-default">Revert Defaults</button>
                <input type="text" name="instant-cart-page-button-style-input" id="instant-cart-page-button-style-input" placeholder="class-name" class="instant-text-input" value='<?php echo str_replace(" ", ",", esc_attr($instant_cart_page_button_style)); ?>'>
                <input type="hidden" id="instant-cart-page-button-class-values" value="<?php echo esc_attr($instant_cart_page_button_style); ?>">
            </div>
            <input type="submit" class="instant-set-button" value="Save Button Classes">
        </form>
    </div>

</div>

<script type="text/javascript">
    var instantPluginApiKeysForm = document.getElementById('instant-api-keys-form');
    var instantCheckoutSettingsForm = document.getElementById('instant-checkout-settings-form');
    var instantButtonStyleForm = document.getElementById('instant-button-style-form');

    async function setAPICredentials() {
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'instantco/v2/admin/credentials')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': "<?php echo esc_js($wp_nonce); ?>"
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body: JSON.stringify({
                "public": document.getElementById("instant-public-key-input").value,
                "secret": document.getElementById("instant-secret-key-input").value
            }) // body data type must match "Content-Type" header
        });
        var resp_body = await resp.json();
        if (resp_body.success) {
            alert("API Credentials saved!");
            return;
        } else {
            alert("Sorry, there was an error in setting the credentials.")
        }
    }

    async function setInstantCheckoutSettings() {
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'instantco/v2/admin/settings')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': "<?php echo esc_js($wp_nonce); ?>"
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body: JSON.stringify({
                "everywhere_button": document.getElementById("instant-everywhere-button-input").checked,
                "override_default_checkout": document.getElementById("instant-override-default-checkout-input").checked
            }) // body data type must match "Content-Type" header
        });
        var resp_body = await resp.json();
        if (resp_body.success) {
            alert("Settings saved!");
            return;
        } else {
            alert("Sorry, there was an error in saving the settings.")
        }
    }

    async function setButtonStyles() {
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'instantco/v2/admin/buttonstyle')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': "<?php echo esc_js($wp_nonce); ?>"
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body: JSON.stringify({
                "checkout": document.getElementById("instant-checkout-page-button-class-values").value,
                "product": document.getElementById("instant-product-page-button-class-values").value,
                "cart": document.getElementById("instant-cart-page-button-class-values").value,
            }) // body data type must match "Content-Type" header
        });
        var resp_body = await resp.json();
        if (resp_body.success) {
            alert("Instant Button CSS classes saved!");
            return;
        } else {
            alert("Sorry, there was an error in setting the button classes.")
        }
    }

    async function defaultButtonStyles(type) {
        var resp = await fetch("<?php echo esc_js(get_rest_url(null, 'instantco/v2/admin/defaultbuttonstyle')); ?>", {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': "<?php echo esc_js($wp_nonce); ?>"
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
            body: JSON.stringify({
                "type": type,
                "checkout": document.getElementById("instant-checkout-page-button-class-values").value,
                "product": document.getElementById("instant-product-page-button-class-values").value,
                "cart": document.getElementById("instant-cart-page-button-class-values").value,
            }) // body data type must match "Content-Type" header
        });
        var resp_body = await resp.json();
        if (resp_body.success) {
            alert(type.charAt(0).toUpperCase() + type.slice(1) + " Page Instant button default classes set!");
            window.location.reload();
            return;
        } else {
            alert("Sorry, there was an error in setting the button classes.")
        }
    }

    if (instantPluginApiKeysForm) {
        instantPluginApiKeysForm.addEventListener('submit', function(event) {
            event.preventDefault();
            setAPICredentials();
        });
    }

    if (instantCheckoutSettingsForm) {
        instantCheckoutSettingsForm.addEventListener('submit', function(event) {
            event.preventDefault();
            setInstantCheckoutSettings();
        });
    }

    if (instantButtonStyleForm) {
        instantButtonStyleForm.addEventListener('submit', function(event) {
            event.preventDefault();
            setButtonStyles();
        });
    }

    document.getElementById('instant-checkout-page-button-style-default').addEventListener('click', function(event) {
        event.preventDefault();
        defaultButtonStyles("checkout");
    });

    document.getElementById('instant-product-page-button-style-default').addEventListener('click', function(event) {
        event.preventDefault();
        defaultButtonStyles("product");
    });

    document.getElementById('instant-cart-page-button-style-default').addEventListener('click', function(event) {
        event.preventDefault();
        defaultButtonStyles("cart");
    });
</script>
<?php
if (!$woo_blocks_installed) {
?>
    <br />
    <div class="error notice">
        <p>
            Please install and activate plugin <a href="https://wordpress.org/plugins/woo-gutenberg-products-block/" target="_blank">WooCommerce Blocks</a> by Automattic. <br />
            Instant Checkout will not function as expected without WooCommerce Blocks installed.
        </p>
    </div>
<?php
}
