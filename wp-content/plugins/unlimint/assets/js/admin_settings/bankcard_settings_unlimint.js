window.addEventListener('load', function () {
    /*global woocommerce_admin_meta_boxes */
    jQuery('#mainform').submit(function (e) {
        const prefix = 'woocommerce_woo-unlimint-custom_woocommerce_unlimint_bankcard_';
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }

        const isValidTerminalCode = validateUlAdminField(prefix + 'terminal_code', 128, 'terminal code', true);
        const isValidTerminalPassword = validateUlAdminField(prefix + 'terminal_password', 128, 'terminal password', false);
        const isValidCallbackSecret = validateUlAdminField(prefix + 'callback_secret', 128, 'callback secret', false);
        const isValidPaymentTitle = validateUlAdminField(prefix + 'payment_title', 128, 'payment title', false);
        const isValidDynamicDescriptor = validateUlAdminField(prefix + 'dynamic_descriptor', 22, 'dynamic descriptor', false);

        if (!isValidTerminalCode || !isValidTerminalPassword || !isValidCallbackSecret || !isValidPaymentTitle || !isValidDynamicDescriptor) {
            e.preventDefault(e);
        }
    });
});

const ulCapturePayment = function () {
    ulProcessPayment('capture', BANKCARD_ALERT_TRANSLATIONS['CAPTURED'], BANKCARD_ALERT_TRANSLATIONS['CAPTURE']);
}

const ulCancelPayment = function () {
    ulProcessPayment('cancel', BANKCARD_ALERT_TRANSLATIONS['CANCELLED'], BANKCARD_ALERT_TRANSLATIONS['CANCEL']);
}

const ulProcessPayment = function (action, status_message, action_message) {
    /*global woocommerce_admin_meta_boxes */
    if (!window.confirm(BANKCARD_ALERT_TRANSLATIONS['ARE_YOU_SURE'] + ' ' + action_message + ' ' + BANKCARD_ALERT_TRANSLATIONS['THE_PAYMENT'])) {
        return;
    }

    jQuery.ajax({
        url: woocommerce_admin_meta_boxes.ajax_url,
        data: {
            action: `wc_ul_${action}`,
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
        },
        type: 'POST',
        success: function (response) {
            const alertPaymentWasNot = BANKCARD_ALERT_TRANSLATIONS['PAYMENT_WAS_NOT'];
            const errorMessage = alertPaymentWasNot + ' ' + status_message;
            if (!response) {
                alert(errorMessage);
                return;
            }
            const responseParsed = JSON.parse(response);
            if (!responseParsed) {
                alert(errorMessage);
                return;
            }

            if (responseParsed.success) {
                alert(BANKCARD_ALERT_TRANSLATIONS['PAYMENT_HAS_BEEN'] + ' ' + status_message + ' ' + BANKCARD_ALERT_TRANSLATIONS['SUCCESSFULLY']);
                location.reload();
            } else {
                if (responseParsed.data && responseParsed.data.error_message) {
                    alert(alertPaymentWasNot + ' ' + status_message + ': ' + responseParsed.data.error_message);
                } else {
                    alert(errorMessage);
                }
            }
        }
    });
}
