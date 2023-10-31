(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimit-ticket', function () {
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
    return validateUlCpf('ul-cpf-ticket');
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
