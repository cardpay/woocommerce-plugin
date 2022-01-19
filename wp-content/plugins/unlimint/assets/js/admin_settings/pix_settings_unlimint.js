window.addEventListener('load', function () {
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimint-pix_woocommerce_unlimint_pix_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        validateAltMethodForm(prefix, e);
    });
});
