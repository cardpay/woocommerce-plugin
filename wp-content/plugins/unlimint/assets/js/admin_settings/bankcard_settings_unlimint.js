const unlimintSettingsEvent = {
    prefix: 'woocommerce_woo-unlimint-custom_woocommerce_unlimint_bankcard_',
    selPaymentPage: null,
    selInstType: null,
    selInstEnabled: null,
    askCpf: null,
    maximumAcceptedInstallments: null,
    installmentsLimits: [],
    installmentsLimitsIF: [1, 3, 6, 9, 12, 18],
    installmentsLimitsMfHold: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
    installmentSettingsFields: ['minimum_installment_amount', 'maximum_accepted_installments', 'installment_type'],
    init: function () {
        this.selPaymentPage = jQuery(`#${this.prefix}payment_page`);
        this.selInstType = jQuery(`#${this.prefix}installment_type`);
        this.selInstAmount = jQuery(`#${this.prefix}minimum_installment_amount`);
        this.selInstEnabled = jQuery(`#${this.prefix}installment_enabled`);
        this.maximumAcceptedInstallments = jQuery(`#${this.prefix}maximum_accepted_installments`);
        this.askCpf = jQuery(`#${this.prefix}ask_cpf`);
        this.installmentSettingsFields = ['minimum_installment_amount', 'maximum_accepted_installments', 'installment_type'];
        this.toggleSettings();
        this.selectPpAndInstType();
        this.processInstallmentSettings();
        this.setupListeners();
        this.twoPrefix();
    },
    setupListeners: function () {
        const obj = this;
        jQuery('#mainform').submit(function (e) {
            obj.onFormSubmit(e);
        });

        jQuery(obj.selPaymentPage).change(function () {
            obj.toggleSettings();

            alert(BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE['API_ACCESS_MODE']);

            const select = `#${obj.prefix}installment_type`;
            if (jQuery(obj.selPaymentPage).val() === 'gateway') {
                jQuery(obj.selInstType).append('<option value="MF_HOLD">' + BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE['MERCHANT_FINANCED'] + '</option>');
            } else {
                jQuery(`${select} option[value="MF_HOLD"]`).remove();
            }

            if (jQuery(obj.selPaymentPage).val() === 'payment_page') {
                obj.selectPpAndInstType();
            }
        });

        jQuery(obj.selInstType).change(function () {
            obj.selectPpAndInstType();
        });

        jQuery(obj.selInstAmount).change(function () {
            obj.twoPrefix();
        });

        jQuery(obj.selInstEnabled).change(function () {
            obj.processInstallmentSettings();
        });

        jQuery(obj.maximumAcceptedInstallments).on('change keyup', function (e) {
            obj.checkMaximumAcceptedInstallments(false, (e.type === 'change'));
        });
    },
    twoPrefix: function () {
        jQuery(`#${this.prefix}minimum_installment_amount`).change(function () {
                const elementByIdToFixed = document.getElementById('woocommerce_woo-unlimint-custom_woocommerce_unlimint_bankcard_minimum_installment_amount');
                const parsed = elementByIdToFixed.value.split('.');
                if (parsed[0] && parsed[1] && parsed[1].length > 4) {
                    const subString = parsed[1].substr(0, 4);
                    return elementByIdToFixed.value = parsed[0] + '.' + subString;
                }

                return elementByIdToFixed.value;
            }
        );
    },
    onFormSubmit: function (e) {
        jQuery('.error.inline').detach();
        let error = false;
        error = !validateUlAdminField(this.prefix + 'terminal_code', 128, 'terminal code', true) || error;
        error = !validateUlAdminField(this.prefix + 'terminal_password', 128, 'terminal password', false) || error;
        error = !validateUlAdminField(this.prefix + 'callback_secret', 128, 'callback secret', false) || error;
        error = !validateUlAdminField(this.prefix + 'payment_title', 128, 'payment title', false) || error;
        error = !validateUlAdminField(this.prefix + 'dynamic_descriptor', 22, 'dynamic descriptor', false) || error;
        error = !this.checkMaximumAcceptedInstallments(true) || error;

        if (error) {
            e.preventDefault(e);
            jQuery('html, body').animate({
                scrollTop: jQuery('.error.inline').offset().top - 200
            }, 1000);
        }
    },
    processInstallmentSettings: function () {
        const obj = this;
        const show = (jQuery(obj.selInstEnabled).val() === 'yes');
        jQuery(obj.installmentSettingsFields).each(function () {
            const el = jQuery(`#${obj.prefix}${this}`).parent().parent().parent();
            if (show) {
                el.show('slow');
            } else {
                el.hide();
            }
        });
    },
    toggleSettings: function () {
        const cpfEl = jQuery(this.askCpf).parent().parent().parent();
        if (jQuery(this.selPaymentPage).val() !== 'gateway') {
            cpfEl.hide();
        } else {
            cpfEl.show();
        }
    },
    normalizeIntVal: function (val) {
        if (val.length === 0) {
            return val;
        }

        val = val.replace(/[^\d](\d*)/g, '');

        return parseInt(val);
    },
    validateInstallmentRange: function (value) {
        const parsed = value.split('-');
        if (parsed.length !== 2) {
            return false;
        }

        parsed[0] = this.normalizeIntVal(parsed[0]);
        parsed[1] = this.normalizeIntVal(parsed[1]);
        let error = (
            this.installmentsLimits.indexOf(parsed[0]) === -1 ||
            this.installmentsLimits.indexOf(parsed[1]) === -1 ||
            parsed[0] >= parsed[1]
        );
        for (let i = parsed[0]; i <= parsed[1]; i++) {
            error = error || (this.installmentsLimits.indexOf(i) === -1);
        }

        return !error;
    },
    fixInstallmentSettings: function (value, defaults) {
        const obj = this;
        if (value.substr(value.length - 1) === ',') {
            value = value.substring(0, value.length - 1);
        }
        const values = value.split(',');

        if (values.length === 0 || (values.length === 1 && values[0] === '')) {
            return defaults;
        }

        const newValues = [];
        jQuery.each(values, function () {
            if (this.indexOf('-') > -1) {
                const vals = this.split('-');
                vals[0] = obj.normalizeIntVal(vals[0]);
                if (vals.length > 1) {
                    vals[1] = obj.normalizeIntVal(vals[1]);
                    newValues.push(vals.join('-'));
                } else {
                    newValues.push(vals[0] + '-');
                }
            } else {
                newValues.push(obj.normalizeIntVal(this));
            }
        });

        return newValues.join(',');
    },
    checkMaximumAcceptedInstallments: function (displayError, fix) {
        const obj = this;
        if (!jQuery(obj.maximumAcceptedInstallments).is(':visible')) {
            return true;
        }

        let error = false;
        let value = jQuery(obj.maximumAcceptedInstallments).val();
        if (fix === true) {
            const defaults = (jQuery(this.selInstType).val() === 'IF') ? '3,6,9,12,18' : '2-12';
            const newValue = this.fixInstallmentSettings(value, defaults);
            if (newValue !== value) {
                window.setTimeout(function () {
                    jQuery(obj.maximumAcceptedInstallments).val(value);
                }, 1);
                value = newValue;
            }
        }

        if (value.search(/[^\d-,]/) !== -1) {
            error = true;
        }

        if (!Number.isNaN(value)) {
            value = value.replace(/NaN/, '');
        }

        const values = value.split(',');
        jQuery.each(values, function () {
            if (this.indexOf('-') > -1) {
                error = error || !(obj.validateInstallmentRange(this));
            } else {
                error = error || (obj.installmentsLimits.indexOf(obj.normalizeIntVal(this)) === -1);
            }
        });

        if (error) {
            if (displayError === true) {
                showUlAdminError('maximum_accepted_installments', 'Allowed installments range');
            }
            highlightUlAdminError(jQuery(obj.maximumAcceptedInstallments).attr('id'));
        }

        if (!error) {
            hideUlAdminError(jQuery(obj.maximumAcceptedInstallments).attr('id'));
        }

        return !error;
    },
    selectPpAndInstType: function () {
        switch (jQuery(this.selInstType).val()) {
            case 'IF': {
                this.installmentsLimits = this.installmentsLimitsIF;
                jQuery(this.maximumAcceptedInstallments).attr('placeholder', '3, 6, 9, 12, 18');
                break;
            }
            case 'MF_HOLD': {
                this.installmentsLimits = this.installmentsLimitsMfHold;
                jQuery(this.maximumAcceptedInstallments).attr('placeholder', '1, 2, 3-5, 7-12');
                break;
            }
            default: {
                //
            }
        }
        this.checkMaximumAcceptedInstallments();
    }
}

window.addEventListener('load', function () {
    const href = window.location.search;
    const sliceUnlimintCusom = href.slice(-19);
    if (sliceUnlimintCusom === 'woo-unlimint-custom') {
        unlimintSettingsEvent.init();
    }
});

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
