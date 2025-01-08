(function ($) {
  'use strict';

  $(function () {
    jQuery('form.checkout').on('checkout_place_order_woo-unlimit-mbway', function () {
      return validatePhoneNumber(
        '#ul-mbway-phone',
        '#ul-mbway-phone-error',
        '#ul-mbway-phone-error-second',
        /^\+?351\d{9}$|^(?!.*[a-zA-Z])\d{9}$/,
        12,
        13,
      );
    });
  });
}(jQuery));

function validateUlMbwayInput() {
  setTimeout(validatePhoneNumber(
    '#ul-mbway-phone',
    '#ul-mbway-phone-error',
    '#ul-mbway-phone-error-second',
    /^\+?351\d{9}$|^(?!.*[a-zA-Z])\d{9}$/,
    12,
    13,
  ), 1);
}
