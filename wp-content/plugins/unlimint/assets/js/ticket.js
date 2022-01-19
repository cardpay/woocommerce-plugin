(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimint-ticket', function () {
            return handleUlBoletoCpf();
        });
    });
}(jQuery));

const handleUlBoletoCpf = function () {
    return handleUlCpf('#ul-cpf-ticket', '#boleto-cpf-error');
}

const handleUlCpf = function (cpfField, errorField) {
    const cpf = jQuery(cpfField);
    const cpfFormatted = getUlCpfFormatted(cpf.val());
    cpf.val(cpfFormatted);

    const cpfError = jQuery(errorField);
    cpfError.hide();
    cpf.removeClass(errorField);

    const isCpfValid = isUlCpfValid(cpfFormatted);
    if (!isCpfValid) {
        cpf.addClass(errorField);
        cpfError.focus();
        cpfError.show();
    }

    return isCpfValid;
}