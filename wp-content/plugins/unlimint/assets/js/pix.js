(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimint-pix', function () {
            return validateUlPixCpf();
        });
    });
}(jQuery));

const validateUlPixCpf = function () {
    return validateUlCpf('ul-cpf-pix');
}
