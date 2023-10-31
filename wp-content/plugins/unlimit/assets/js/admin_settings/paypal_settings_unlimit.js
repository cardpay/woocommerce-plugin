window.addEventListener('load', function () {
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimit-paypal_woocommerce_unlimit_paypal_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        validateAltMethodForm(prefix, e);
    });
    jQuery('#woocommerce_woo-unlimit-paypal_woocommerce_unlimit_paypal_payment_page').change(function () {
        alert(UNLIMIT_ALERT_TRANSLATIONS_CHANGE_MODE['API_ACCESS_MODE']);
    });
});
