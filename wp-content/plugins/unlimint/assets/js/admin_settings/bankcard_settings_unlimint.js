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
    /*global woocommerce_admin_meta_boxes */
    if (!window.confirm('Are you sure you want to capture the payment?')) {
        return;
    }

    jQuery.ajax({
        url: woocommerce_admin_meta_boxes.ajax_url,
        data: {
            action: 'wc_ul_capture',
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
        },
        type: 'POST',
        success: function (response) {
            const errorMessage = 'Payment was not captured';
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
                alert('Payment has been captured successfully');
                location.reload();
            } else {
                if (responseParsed.data && responseParsed.data.error_message) {
                    alert(`Payment capture has failed: ${responseParsed.data.error_message}`);
                } else {
                    alert(errorMessage);
                }
            }
        }
    });
}

const ulCancelPayment = function () {
    /*global woocommerce_admin_meta_boxes */
    if (!window.confirm('Are you sure you want to cancel the payment?')) {
        return;
    }

    jQuery.ajax({
        url: woocommerce_admin_meta_boxes.ajax_url,
        data: {
            action: 'wc_ul_cancel',
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
        },
        type: 'POST',
        success: function (response) {
            const errorMessage = 'Payment was not cancelled';
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
                alert('Payment has been cancelled successfully');
                location.reload();
            } else {
                if (responseParsed.data && responseParsed.data.error_message) {
                    alert(`Payment cancellation has failed: ${responseParsed.data.error_message}`);
                } else {
                    alert(errorMessage);
                }
            }
        }
    });
}
