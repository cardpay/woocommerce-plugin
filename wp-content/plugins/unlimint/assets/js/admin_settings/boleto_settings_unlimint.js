window.addEventListener('load', function () {
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimint-ticket_wc_ul_boleto_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        const isValidTerminalCode = validateUlAdminField(prefix + 'terminal_code', 128, 'terminal code', true);
        const isValidTerminalPassword = validateUlAdminField(prefix + 'terminal_password', 128, 'terminal password', false);
        const isValidCallbackSecret = validateUlAdminField(prefix + 'callback_secret', 128, 'callback secret', false);
        const isValidPaymentTitle = validateUlAdminField(prefix + 'payment_title', 128, 'payment title', false);

        if (!isValidTerminalCode || !isValidTerminalPassword || !isValidCallbackSecret || !isValidPaymentTitle) {
            e.preventDefault(e);
        }
    });
});
