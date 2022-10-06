(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimint-ticket', function () {
            return validateUlBoletoCpf();
        });
    });
}(jQuery));

const formatUlBoletoCpf = function (cpfFieldId) {
    const cpfField = jQuery(`#${cpfFieldId}`);
    if (!cpfField.length) {
        return cpfField.val();
    }

    const cpfFormatted = formatUlCpf(cpfField.val());
    cpfField.val(cpfFormatted);

    return cpfFormatted;
}

const validateUlBoletoCpf = function () {
    const zip = validateUlBoletoZip();
    const cpf = validateUlCpf('ul-cpf-ticket');
    return zip && cpf;
}

const checkZip = function(id) {
    const zipField = jQuery('#' + id);
    zipField.removeClass('ul-form-control-error');
    if (!zipField.is(':visible')) {
        return true;
    }
    const zip = zipField.val().replace(/\D/g, '');
    if(zip.length!=8) {
        zipField.addClass('ul-form-control-error');
        return false;
    }
    return true;
}
const validateUlBoletoZip = function () {
    const shippingCheck = checkZip('shipping_postcode');
    const billingCheck = checkZip('billing_postcode');
    if (!shippingCheck) {
            alert(getShippingZipErrorMessage());
    }
    if (!billingCheck) {
            alert(getBillingZipErrorMessage());
    }
    return shippingCheck && billingCheck;
}

const validateUlCpf = function (cpfFieldId) {
    const cpfField = jQuery(`#${cpfFieldId}`);

    const cpfError = jQuery(`#${cpfFieldId}-error`);
    const cpfFieldSecond = jQuery(`#${cpfFieldId}-error-second`);

    if (cpfField.val().length === 0) {
        cpfError.hide();
        cpfField.addClass(UL_ERROR_CLASS);
        cpfFieldSecond.show();
        return false;
    }
    cpfFieldSecond.hide();

    if (!cpfField.length) {
        return true;
    }

    cpfField.removeClass(UL_ERROR_CLASS);

    if (!cpfError.length) {
        return true;
    }
    cpfError.hide();

    let isCardFieldValid = true;

    const cpfFormatted = formatUlBoletoCpf(cpfFieldId);
    const isCpfValid = isUlCpfValid(cpfFormatted);
    if (!isCpfValid) {
        cpfField.addClass(UL_ERROR_CLASS);
        cpfError.focus();
        cpfError.show();
        isCardFieldValid = false;
    }

    return isCardFieldValid;
}
