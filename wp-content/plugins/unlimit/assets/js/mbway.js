(function ($) {
    'use strict';

    $(function () {
        $('form.checkout').on('checkout_place_order_woo-unlimit-mbway', function () {
            return validateUlMbwayPhone();
        });
    });
}(jQuery));

function validateUlMbwayInput() {
    setTimeout(validateUlMbwayPhone(), 1);
}

const validateUlMbwayPhone = function () {
    var phoneField = jQuery('#ul-mbway-phone');
    var phoneErrField = jQuery('#ul-mbway-phone-error');
    var phoneErrFieldSecond = jQuery('#ul-mbway-phone-error-second');

    phoneField.val(phoneField.val().replace(/[^+\d]/g, ''));

    var numbersOnly = phoneField.val().trim();
    var phonePattern = /^\+?351\d{9}$|^(?!.*[a-zA-Z])\d{9}$/;

    if (numbersOnly === '') {
        phoneErrField.hide();
        phoneErrFieldSecond.show();
        phoneField.addClass(UL_ERROR_CLASS);
        return false;
    }

    if (!numbersOnly.match(phonePattern)) {
        phoneErrFieldSecond.hide();
        phoneErrField.show();
        phoneField.addClass(UL_ERROR_CLASS);
        return false;
    }

    phoneField.removeClass(UL_ERROR_CLASS);
    phoneErrField.hide();
    phoneErrFieldSecond.hide();
    return true;
}