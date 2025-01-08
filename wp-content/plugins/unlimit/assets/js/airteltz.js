const AIRTELTZ_PHONE = '#ul-airteltz-phone';

(function ($) {
  'use strict';

  $(function () {
    $('form.checkout').on('checkout_place_order_woo-unlimit-airteltz', function () {
      return validatePhoneNumber(
        AIRTELTZ_PHONE,
        '#ul-airteltz-phone-error',
        '#ul-airteltz-phone-error-second',
        /^\+255\d{9}$/,
        13,
        13,
      );
    });
  });
}(jQuery));

function validateUlAirteltzInput() {
  autoFillCountryCode(jQuery(AIRTELTZ_PHONE), '+255');

  setTimeout(validatePhoneNumber(
    AIRTELTZ_PHONE,
    '#ul-airteltz-phone-error',
    '#ul-airteltz-phone-error-second',
    /^\+255\d{9}$/,
    13,
      13,
  ), 1);
}
