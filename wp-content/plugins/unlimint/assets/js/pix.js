(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimint-pix', function () {
            return handleUlPixCpf();
        });
    });
}(jQuery));

const handleUlPixCpf = function () {
    return handleUlCpf('#ul-cpf-pix', '#pix-cpf-error');
}
