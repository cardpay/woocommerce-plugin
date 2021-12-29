(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimint-ticket', function () {
            return handleUlCpf();
        });
    });
}(jQuery));

const handleUlCpf = function () {
    const cpf = jQuery('#ul-cpf-ticket');
    const cpfFormatted = getUlCpfFormatted(cpf.val());
    cpf.val(cpfFormatted);

    const cpfError = jQuery('#boleto-cpf-error');
    cpfError.hide();
    cpf.removeClass('ul-form-control-error');

    const isCpfValid = isUlCpfValid(cpfFormatted);
    if (!isCpfValid) {
        cpf.addClass('ul-form-control-error');
        cpfError.focus();
        cpfError.show();
    }

    return isCpfValid;
}