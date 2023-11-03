document.addEventListener('DOMContentLoaded', function () {
    var addButtonAfter = document.querySelector('.refund-items');
    if (addButtonAfter) {
        var captureButton = document.createElement('button');
        captureButton.type = 'button';
        captureButton.id = 'ul_button_capture';
        captureButton.className = 'button';
        captureButton.style.color = '#000000';
        captureButton.textContent = unlimit_vars.bankcard_translations.capture;
        captureButton.onclick = ulCapturePayment;

        var cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.id = 'ul_button_cancel';
        cancelButton.className = 'button';
        cancelButton.style.color = '#000000';
        cancelButton.textContent = unlimit_vars.bankcard_translations.cancel;
        cancelButton.onclick = ulCancelPayment;

        addButtonAfter.parentNode.insertBefore(cancelButton, addButtonAfter.nextSibling);
        addButtonAfter.parentNode.insertBefore(captureButton, addButtonAfter.nextSibling);
    }
});

const ulCapturePayment = function () {
    ulProcessPayment('capture', unlimit_vars.bankcard_translations.captured,
        unlimit_vars.bankcard_translations.capture);
};

const ulCancelPayment = function () {
    ulProcessPayment('cancel', unlimit_vars.bankcard_translations.cancelled,
        unlimit_vars.bankcard_translations.cancel);
};

const ulProcessPayment = function (action, statusMessage, actionMessage) {
    /*global woocommerce_admin_meta_boxes */
    if (!window.confirm(
        `${unlimit_vars.bankcard_translations.are_you_sure} ${actionMessage} ${unlimit_vars.bankcard_translations.the_payment}`)) {
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
            const alertPaymentWasNot = unlimit_vars.bankcard_translations.payment_was_not;
            const errorMessage = `${alertPaymentWasNot} ${statusMessage}`;
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
                alert(`${unlimit_vars.bankcard_translations.payment_has_been} ${statusMessage} ${unlimit_vars.bankcard_translations.successfully}`);
                location.reload();
            } else {
                if (responseParsed.data && responseParsed.data['error_message']) {
                    alert(`${alertPaymentWasNot} ${statusMessage}: ${responseParsed.data.error_message}`);
                } else {
                    alert(errorMessage);
                }
            }
        },
    });
};
