const formatUlCpf = function (cpf) {
    return cpf.replace(/\D/g, '')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
};
const unlimitIframePaymentMethods = [];

const isUlCpfValid = function (cpf) {
    if (!cpf) {
        return false;
    }

    cpf = cpf.replace(/[\s.-]*/igm, '');
    if (
        !cpf ||
        cpf.length !== 11 ||
        cpf === '00000000000' ||
        cpf === '11111111111' ||
        cpf === '22222222222' ||
        cpf === '33333333333' ||
        cpf === '44444444444' ||
        cpf === '55555555555' ||
        cpf === '66666666666' ||
        cpf === '77777777777' ||
        cpf === '88888888888' ||
        cpf === '99999999999'
    ) {
        return false;
    }

    let sum = 0;
    let remainder;
    for (let i = 1; i <= 9; i++) {
        sum = sum + parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }

    remainder = parseInt(sum * 10) % 11;
    if ((remainder === 10) || (remainder === 11)) {
        remainder = 0;
    }

    if (remainder !== parseInt(cpf.substring(9, 10))) {
        return false;
    }

    sum = 0;
    for (let j = 1; j <= 10; j++) {
        sum = sum + parseInt(cpf.substring(j - 1, j)) * (12 - j);
    }

    remainder = parseInt((sum * 10) % 11);
    if ((remainder === 10) || (remainder === 11)) {
        remainder = 0;
    }

    return remainder === parseInt(cpf.substring(10, 11));
};
var unlimitFormCheckout;
jQuery(document).ready(function () {
    unlimitFormCheckout = jQuery('form.checkout');
})
const unlimitIframeProcessor = {
    oldEvents: false,
    maxIframeWidth: 1000,
    iframePadding: 80,
    spinner: '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table',
    beforeSubmit: function () {
        jQuery(this.spinner).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: .6,
            },
        });
    },
    afterSubmit: function () {
        unlimitFormCheckout.removeClass('processing');
        jQuery(this.spinner).unblock();
    },
    buttonClick: function () {
        const isCreditCard = jQuery('#payment_method_woo-unlimit-custom').is(':checked');
        const isMbway = jQuery('#payment_method_woo-unlimit-mbway').is(':checked');
        const isMultibanco = jQuery('#payment_method_woo-unlimit-multibanco').is(':checked');
        const isPaypal = jQuery('#payment_method_woo-unlimit-paypal').is(':checked');
        const isSpei = jQuery('#payment_method_woo-unlimit-spei').is(':checked');
        const isSepa = jQuery('#payment_method_woo-unlimit-sepa').is(':checked');

        if (
            (isCreditCard && !unlimitIframePaymentMethods.includes('creditcard')) ||
            (isMbway && !unlimitIframePaymentMethods.includes('mbway')) ||
            (isMultibanco && !unlimitIframePaymentMethods.includes('multibanco')) ||
            (isPaypal && !unlimitIframePaymentMethods.includes('paypal')) ||
            (isSpei && !unlimitIframePaymentMethods.includes('spei')) ||
            (isSepa && !unlimitIframePaymentMethods.includes('sepa'))
        ) {
            unlimitIframeProcessor.oldEvents = false;
            return;
        }

        if (!isMbway && !isCreditCard && !isMultibanco && !isPaypal && !isSpei && !isSepa && unlimitIframeProcessor.oldEvents !== false) {
            unlimitFormCheckout.unbind('submit');
            const events = unlimitIframeProcessor.oldEvents;
            for (const type in events) {
                for (const handler in events[type]) {
                    jQuery.event.add(
                        unlimitFormCheckout,
                        type,
                        events[type][handler],
                        events[type][handler].data);
                }
            }
            unlimitIframeProcessor.oldEvents = false;
            return;
        }
        if ((isMbway || isCreditCard || isMultibanco || isPaypal || isSpei || isSepa) && unlimitIframeProcessor.oldEvents === false) {
            unlimitIframeProcessor.oldEvents = unlimitFormCheckout.data('events',
                'submit');
            unlimitFormCheckout.unbind('submit').submit(function (event) {
                event.preventDefault();
                unlimitIframeProcessor.formSubmit();
            });
        }
    },
    scroll_to_notices: function () {
        let scrollElement = jQuery(
            '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout');
        if (!scrollElement.length) {
            scrollElement = unlimitFormCheckout;
        }
        jQuery.scroll_to_notices(scrollElement);
    },
    submit_error: function (errorMessage) {
        jQuery(
            '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        unlimitFormCheckout.prepend(
            `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">${errorMessage}</div>`); // eslint-disable-line max-len
        unlimitFormCheckout.removeClass('processing').unblock();
        this.scroll_to_notices();
        jQuery(document.body).trigger('checkout_error', [errorMessage]);
    },
    redirectFunc: function (url) {
        jQuery('.unlimit_modal_bg').removeClass('closed_unlimit');
        jQuery('body').css('overflow', 'hidden');
        jQuery('.unlimit_modal_iframe').attr('src', url);
    },
    onSuccessSubmit: function (e) {
        unlimitIframeProcessor.afterSubmit();
        unlimitFormCheckout.removeClass('processing');
        try {
            if ('success' !== e.result) {
                if ('failure' === e.result) {
                    throw new Error('Result failure');
                } else {
                    throw new Error('Invalid response');
                }
            }
            -1 === e.redirect.indexOf('https://') || -1 ===
            e.redirect.indexOf('http://')
                ? this.redirectFunc(e.redirect)
                : this.redirectFunc(decodeURI(e.redirect));
        } catch (t) {
            if (!0 === e.reload) {
                window.location.reload();
                return;
            }
            const messages = (
                (
                    'array' === typeof e['messages']
                ) && e['messages'].length > 0
            ) ? e['messages'] : [];
            !0 === (
                e.refresh && g(document.body).trigger('update_checkout') &&
                messages.length === 0
            )
                ?
                this.submit_error(messages)
                :
                this.submit_error(
                    `<div class="woocommerce-error">${wc_checkout_params.i18n_checkout_error}</div>`);
        }
    },
    formSubmit: function () {
        const obj = this;
        if (unlimitFormCheckout.hasClass('processing')) {
            return;
        }
        obj.beforeSubmit();
        jQuery.ajax({
            type: 'POST',
            url: wc_checkout_params.checkout_url,
            data: unlimitFormCheckout.serialize(),
            dataType: 'json',
            success: function (e) {
                obj.onSuccessSubmit(e);
            },
            error: function (_e, _t, o) {
                unlimitIframeProcessor.afterSubmit();
                obj.submit_error(`<div class="woocommerce-error">${o}</div>`);
            },
        });
    },
    setModalSize: function () {
        const backWindow = jQuery('.unlimit_modal_page');
        const w = jQuery(window).width();
        const {iframePadding, maxIframeWidth} = this;

        const marginTop = 40;
        const marginBottom = 20;

        const newWidth = Math.min(w - iframePadding, maxIframeWidth);
        const margin = Math.round((w - newWidth) / 2);

        backWindow.css({
            'background': '#FFF',
            'max-height': '800px',
            'height': '100%',
            'border-radius': '10px',
            'padding': '10px',
            'box-shadow': '0 0 10px rgba(0, 0, 0, 0.2)',
            'margin-top': marginTop + 'px',
            'margin-left': margin + 'px',
            'margin-bottom': marginBottom + 'px',
            'width': newWidth + 'px',
        });
    },
};
jQuery(window).resize(function () {
    unlimitIframeProcessor.setModalSize();
});

jQuery(document).on('click', '#place_order', function () {
    const isGPaySelected = jQuery('#payment_method_woo-unlimit-gpay').is(':checked');

    if (isGPaySelected) {
        if (!isModalOpen) {
            // If GPay is selected and the modal is not open, open the modal
            isModalOpen = true;
            event.preventDefault();
            document.getElementById('buttonContainer').querySelector('button').click();
        } else {
            // If GPay is selected and the modal is already open, process the payment
            unlimitIframeProcessor.setModalSize();
            unlimitIframeProcessor.buttonClick();
        }
    } else {
        // If GPay is not selected, perform other actions
        unlimitIframeProcessor.setModalSize();
        unlimitIframeProcessor.buttonClick();
    }
});



