window.addEventListener('load', function () {
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimit-apay_woocommerce_unlimit_apay_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        validateAltMethodForm(prefix, e);
        const isValidAppleMerchantId = validateUlAdminField('woocommerce_woo-unlimit-apay_apple_merchant_id', 128, 'Apple merchant ID', false);
        if (!isValidAppleMerchantId) {
            e.preventDefault(e);
        }
    });
});
