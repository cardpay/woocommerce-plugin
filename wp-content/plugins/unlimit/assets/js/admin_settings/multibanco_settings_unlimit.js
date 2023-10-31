window.addEventListener('load', function () {
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimit-multibanco_woocommerce_unlimit_multibanco_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        validateAltMethodForm(prefix, e);
    });
    jQuery('#woocommerce_woo-unlimit-multibanco_woocommerce_unlimit_multibanco_payment_page').change(function () {
        alert(UNLIMIT_ALERT_TRANSLATIONS_CHANGE_MODE['API_ACCESS_MODE']);
    });
});
