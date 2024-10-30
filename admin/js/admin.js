(function ($) {
    ('use strict');

    // The DOM element you wish to replace with Tagify
    var checkoutPageButtonClassInput = document.querySelector(
        'input#instant-checkout-page-button-style-input'
    );
    var productPageButtonClassInput = document.querySelector(
        'input#instant-product-page-button-style-input'
    );
    var cartPageButtonClassInput = document.querySelector(
        'input#instant-cart-page-button-style-input'
    );

    function valueFormatter(valuesArr) {
        return valuesArr.map((item) => item.value).join(' ');
    }

    // initialize Tagify on the above input node reference
    new Tagify(checkoutPageButtonClassInput, {
        originalInputValueFormat: valueFormatter,
    });
    new Tagify(productPageButtonClassInput, {
        originalInputValueFormat: valueFormatter,
    });
    new Tagify(cartPageButtonClassInput, {
        originalInputValueFormat: valueFormatter,
    });

    checkoutPageButtonClassInput.addEventListener(
        'change',
        onChangeCheckoutButtonClass
    );
    productPageButtonClassInput.addEventListener(
        'change',
        onChangeProductButtonClass
    );
    cartPageButtonClassInput.addEventListener(
        'change',
        onChangeCartButtonClass
    );

    function onChangeCheckoutButtonClass(e) {
        document.getElementById(
            'instant-checkout-page-button-class-values'
        ).value = e.target.value;
    }
    function onChangeProductButtonClass(e) {
        document.getElementById(
            'instant-product-page-button-class-values'
        ).value = e.target.value;
    }
    function onChangeCartButtonClass(e) {
        document.getElementById('instant-cart-page-button-class-values').value =
            e.target.value;
    }
})(jQuery);
