window.addEventListener('load', function () {
    const prefix = 'woocommerce_woo-unlimint-custom_woocommerce_unlimint_bankcard_';
    const selPaymentPage = document.getElementById(prefix + 'payment_page');
    const selInstType = document.getElementById(prefix + 'installment_type');
    const selInstEnabled = document.getElementById(prefix + 'installment_enabled');
    const askCpf = document.getElementById(prefix + 'ask_cpf');
    const installmentSettingsFields = ['minimum_total_amount', 'maximum_accepted_installments', 'installment_type'];

    const href = window.location.search;
    const sliceUnlimintCusom = href.slice(-19);
    if (sliceUnlimintCusom === 'woo-unlimint-custom') {
        toggleSettings(selPaymentPage, askCpf);
        selectInstallmentType(selInstType, prefix);
        selectPaymentPage(selInstType, selPaymentPage, prefix, askCpf);
        processInstallmentSettings(selInstEnabled, prefix, installmentSettingsFields);
    }

    jQuery(selInstEnabled).change(function () {
        processInstallmentSettings(selInstEnabled, prefix, installmentSettingsFields);
    });

    /*global woocommerce_admin_meta_boxes */
    jQuery('#mainform').submit(function (e) {
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

const processInstallmentSettings = function (selInstEnabled, prefix, fields) {
    const show = (jQuery(selInstEnabled).val() === 'yes');
    jQuery(fields).each(function () {
        const el = jQuery(`#${prefix}${this}`).parent().parent().parent();
        if (show) {
            el.show('slow');
        } else {
            el.hide();
        }
    });
}

const selectPaymentPage = function (selInstType, selPaymentPage, prefix, askCpf) {
    selPaymentPage.addEventListener('change', function () {
        toggleSettings(selPaymentPage, askCpf);

        alert('API access mode is changed, please check Terminal code, terminal password, callback secret values. '
            + 'After changing of the API mode in plugin also must be changed API access mode in Unlimint. '
            + 'Please consult about it with Unlimint support.');

        const select = `#${prefix}installment_type`;
        if (selPaymentPage.value === 'gateway') {
            jQuery(select).append('<option value="MF_HOLD">Merchant financed</option>');
        } else {
            jQuery(`${select} option[value="MF_HOLD"]`).remove();
        }

        if (selPaymentPage.value === 'payment_page') {
            selectPpAndInstType(prefix, selInstType.value);
        }
    });
}

const toggleSettings = function (selPaymentPage, askCpf) {
    const cpfEl = jQuery(askCpf).parent().parent().parent();
    if (selPaymentPage.value !== 'gateway') {
        cpfEl.hide();
    } else {
        cpfEl.show();
    }
}

const selectPpAndInstType = function (prefix, instType) {
    const maximumAcceptedInstallments = jQuery(`#${prefix}maximum_accepted_installments`);
    maximumAcceptedInstallments.empty();

    const countMaximumAcceptedInstallmentsFor = (instType === 'IF') ?
        [
            ['3', 3],
            ['6', 6],
            ['9', 9],
            ['12', 12],
            ['18', 18],
        ] :
        [
            ['1', 1],
            ['2', 2],
            ['3', 3],
            ['4', 4],
            ['5', 5],
            ['6', 6],
            ['7', 7],
            ['8', 8],
            ['9', 9],
            ['10', 10],
            ['11', 11],
            ['12', 12],
        ];

    for (let i = 0; i < countMaximumAcceptedInstallmentsFor.length; i++) {
        maximumAcceptedInstallments.append('<option value="' + countMaximumAcceptedInstallmentsFor[i][0] + '">' + countMaximumAcceptedInstallmentsFor[i][1] + '</option>');
    }
}

const selectInstallmentType = function (selInstType, prefix) {
    selInstType.addEventListener('change', function () {
        selectPpAndInstType(prefix, selInstType.value);
    });
}

const ulCapturePayment = function () {
    ulProcessPayment('capture', BANKCARD_ALERT_TRANSLATIONS['CAPTURED'], BANKCARD_ALERT_TRANSLATIONS['CAPTURE']);
}

const ulCancelPayment = function () {
    ulProcessPayment('cancel', BANKCARD_ALERT_TRANSLATIONS['CANCELLED'], BANKCARD_ALERT_TRANSLATIONS['CANCEL']);
}

const ulProcessPayment = function (action, statusMessage, actionMessage) {
    /*global woocommerce_admin_meta_boxes */
    if (!window.confirm(`${BANKCARD_ALERT_TRANSLATIONS['ARE_YOU_SURE']} ${actionMessage} ${BANKCARD_ALERT_TRANSLATIONS['THE_PAYMENT']}`)) {
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
                alert(`${BANKCARD_ALERT_TRANSLATIONS['PAYMENT_HAS_BEEN']} ${statusMessage} ${BANKCARD_ALERT_TRANSLATIONS['SUCCESSFULLY']}`);
                location.reload();
            } else {
                if (responseParsed.data && responseParsed.data['error_message']) {
                    alert(`${alertPaymentWasNot} ${statusMessage}: ${responseParsed.data.error_message}`);
                } else {
                    alert(errorMessage);
                }
            }
        }
    });
}
