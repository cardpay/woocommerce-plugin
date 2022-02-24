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
    return validateUlCpf('ul-cpf-ticket', 'boleto-cpf-error');
}

const validateUlCpf = function (cpfFieldId, errorField) {
    const cpfField = jQuery(`#${cpfFieldId}`);
    cpfField.removeClass(UL_ERROR_CLASS);

    const cpfError = jQuery(`#${errorField}`);
    cpfError.hide();

    const cpfFormatted = formatUlBoletoCpf(cpfFieldId);
    const isCpfValid = isUlCpfValid(cpfFormatted);
    if (!isCpfValid) {
        cpfField.addClass(UL_ERROR_CLASS);
        cpfError.focus();
        cpfError.show();
    }

    return isCpfValid;
}
