const UL_ERROR_CLASS = 'ul-form-control-error';

const UL_CARD_NUMBER = 'ul-card-number';
const UL_CARD_HOLDER_NAME = 'ul-card-holder-name';
const UL_CARD_EXPIRATION_DATE = 'ul-card-expiration-date';
const UL_CVC = 'ul-cvc';
const UL_INSTALLMENTS = 'ul-installments';
const UL_CPF = 'ul-cpf';
const UL_MAX_CARD_EXPIRATION_YEARS = 40;

const formCheckout = jQuery('form.checkout');

const checkForm = {
    INPUT_UL_FIELD_IDS: [UL_CARD_NUMBER, UL_CARD_HOLDER_NAME, UL_CARD_EXPIRATION_DATE, UL_CVC, UL_INSTALLMENTS, UL_CPF],
    check: function () {
        let areInputFieldsValid = true;

        for (const fieldId of this.INPUT_UL_FIELD_IDS) {
            if (!validateUlCardField(fieldId)) {
                areInputFieldsValid = false;
            }
        }
        return areInputFieldsValid;
    }
};

(function (jQuery) {
    jQuery(function () {
        formCheckout.on('checkout_place_order_woo-unlimit-custom', function () {
            const inputFieldError = jQuery(`#ul-filing-id-error`);
            inputFieldError.hide();
            const inputField = jQuery('.select_filing_id');

            if (jQuery('input[name="unlimit_custom[filing_id]"]').length > 0 &&
                jQuery('input[name="unlimit_custom[filing_id]"]:checked').length === 0 &&
                !jQuery('#unlimit-form').is(':visible')) {
                if (inputField.is(':visible')) {
                    inputField.addClass(UL_ERROR_CLASS);
                    inputFieldError.show();
                    return false;
                }
            }
            return checkForm.check();
        });

        jQuery(document).on('change', 'input[name="unlimit_custom[filing_id]"]', function () {
            jQuery(`#ul-filing-id-error`).hide();
            jQuery('.select_filing_id').removeClass(UL_ERROR_CLASS);
            if (jQuery('input[name="unlimit_custom[filing_id]"]:checked').val() !== "0") {
                jQuery('#unlimit-form').hide();
            } else {
                jQuery('#unlimit-form').show();
            }
        });

        jQuery(document).on('click', '.add_new_card .add-card', function () {
            jQuery('#unlimit-form').slideToggle();
            jQuery('input[name="unlimit_custom[filing_id]"]:checked').prop('checked', false);
            jQuery('.select_filing_id').animate({
                scrollTop: jQuery("#unlimit-form").offset().top - jQuery(".ul-panel-custom-checkout").offset().top - 50
            }, 500);
        });

        jQuery(document).on('click', '.init-delete-card', function () {
            jQuery(this).hide();
            jQuery(this).parent().find('.delete-card-wrapper').show();
        });

        jQuery(document).on('click', '.delete-cancel', function () {
            jQuery(this).parent().hide();
            jQuery(this).parent().next().show();
        });

        jQuery(document).on('click', '.delete-card', function () {
            deleteCard(jQuery(this));
        });
    });
}(jQuery));

const deleteCard = function (el) {
    jQuery.post(getCheckoutConfigParam('ajax_url', ''), {
        action: 'delete_card',
        nonce: getCheckoutConfigParam('ajax_nonce', ''),
        recurring_data_id: el.val()
    }, function (data) {
        const response = JSON.parse(data);
        if (response.success) {
            jQuery('.unlimit-custom-success').text(response.message).show();
            el.parent().parent().remove();
            checkAndHideSelectFiling();

            setTimeout(function () {
                jQuery('.unlimit-custom-success').fadeOut(1000);
            }, 3000);
        } else {
            jQuery('.unlimit-custom-error').text(response.message).show();
        }
    }, 'text')
        .fail(function (xhr, textStatus, errorThrown) {
            console.log(xhr.responseText);
        });
};

const checkAndHideSelectFiling = function () {
    let hasCards = false;

    jQuery('.select_filing_id').each(function () {
        if (jQuery(this).find('.filing_id_wrapper').length) {
            hasCards = true;
            jQuery(this).show();
        } else {
            jQuery(this).hide();
        }
    });

    if (!hasCards) {
        jQuery('#unlimit-form').show();
    } else {
        jQuery('#unlimit-form').hide();
    }
};

const getCheckoutConfigParam = function (type, defaultValue) {
    if (typeof window.wc_unlimit_custom_params === 'undefined' || typeof window.wc_unlimit_custom_params[type] === 'undefined') {
        return defaultValue;
    }
    return window.wc_unlimit_custom_params[type];
}


const formatUlCardField = function (fieldId) {
    const inputField = jQuery('#' + fieldId);
    if (!inputField.length) {
        return;
    }

    switch (fieldId) {
        case UL_CARD_NUMBER:
            formatUlCardNumber();
            break;

        case UL_CARD_EXPIRATION_DATE:
            formatUlExpirationDate();
            break;

        case UL_CVC:
            formatUlCvc();
            break;

        case UL_CPF:
            const newCpfValue = formatUlCpf(inputField.val());
            inputField.val(newCpfValue);
            break;

        default:
            break;
    }
}

const validateUlCardField = function (fieldId) {
    const inputField = jQuery('#' + fieldId);
    if (!inputField.is(':visible')) {
        return true;
    }
    const inputFieldError = jQuery(`#${fieldId}-error`);
    const inputFieldErrorSecond = jQuery(`#${fieldId}-error-second`);

    if (inputField.val().length === 0) {
        inputFieldError.hide();
        inputField.addClass(UL_ERROR_CLASS);
        inputFieldErrorSecond.show();

        return false;
    }
    inputFieldErrorSecond.hide();

    if (!inputField.length) {
        return true;
    }

    if (inputField[0].id === 'ul-card-holder-name' && (inputField.val().length < 2 || inputField.val().length > 50)) {
        inputFieldError.show();
        inputField.addClass(UL_ERROR_CLASS);

        return false;
    }

    inputField.removeClass(UL_ERROR_CLASS);
    inputFieldError.hide();

    if (!inputFieldError.length) {
        return true;
    }

    let isCardFieldValid = true;

    if (!isUlInputFieldValid(fieldId, inputField)) {
        inputField.addClass(UL_ERROR_CLASS);
        inputFieldError.show();
        isCardFieldValid = false;
    }

    return isCardFieldValid;
}

const isUlInputFieldValid = function (fieldId, inputField) {
    formatUlCardField(fieldId);

    let isFieldValid;
    const fieldValue = inputField.val();

    switch (fieldId) {
        case UL_CARD_NUMBER:
            isFieldValid = isUlCreditCardNumberValid();
            break;

        case UL_CARD_HOLDER_NAME:
            isFieldValid = /\S/.test(fieldValue);
            break;

        case UL_CARD_EXPIRATION_DATE:
            isFieldValid = isUlExpirationDateValid(fieldValue);
            break;

        case UL_CVC:
            isFieldValid = isUlCvcValid();
            break;

        case UL_INSTALLMENTS:
            isFieldValid = areUlInstallmentsValid();
            break;

        case UL_CPF:
            isFieldValid = isUlCpfValid(fieldValue);
            break;

        default:
            isFieldValid = (fieldValue.length > 0);
            break;
    }

    return isFieldValid;
}

const isUlLuhnAlgorithmPassed = function (cardNumber) {
    if (!cardNumber) {
        return false;
    }

    const cardNumberWithoutSpaces = (cardNumber + '').replace(/\s/g, '');
    let digit, odd, sum, _i, _len;
    odd = true;
    sum = 0;
    const digits = cardNumberWithoutSpaces.split('').reverse();

    for (_i = 0, _len = digits.length; _i < _len; _i++) {
        digit = digits[_i];
        digit = parseInt(digit, 10);
        odd = !odd;
        if (odd) {
            digit *= 2;
        }
        if (digit > 9) {
            digit -= 9;
        }
        sum += digit;
    }

    return (sum % 10 === 0);
}

const formatUlCardNumber = function () {
    const cardNumber = jQuery('#ul-card-number');
    if (!cardNumber.length) {
        return;
    }

    const cardNumberFormatted = cardNumber.val().replace(/\D/g, '')
        .replace(/^(\d{4})(\d)/g, "$1 $2")
        .replace(/^(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3")
        .replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4")
        .replace(/^(\d{4})\s(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4 $5");

    cardNumber.val(cardNumberFormatted);
}

const isUlCreditCardNumberValid = function () {
    const CARD_BRANDS = [
        {
            cbType: 'visa',
            pattern: /^4/,
            cnLength: [13, 14, 15, 16, 19],
        },
        {
            cbType: 'mir',
            pattern: /^220[0-4]\d+/,
            cnLength: [16, 17, 18, 19],
        },
        {
            cbType: 'discover',
            pattern: /^(60110\d|6011[2-4]\d|601174|60117[7-9]|6011[8-9][4-9]|644\d\d\d|65\d\d\d\d|64[4-9]\d+|369989)/,
            cnLength: [16, 17, 18, 19],
        },
        {
            cbType: 'dinersclub',
            pattern: /^(30[0-5]\d\d\d|3095\d\d|3[8-9]\d\d\d\d)/,
            cnLength: [16, 17, 18, 19],
        }, {
            cbType: 'dinersclub',
            pattern: /^(36\d\d\d\d)/,
            cnLength: [14, 15, 16, 17, 18, 19],
        },
        {
            cbType: 'amex',
            pattern: /^3[47]/,
            cnLength: [15],
        },
        {
            cbType: 'jcb',
            pattern: /^(((352[8-9][0-9][0-9])|(35[3-8][0-9][0-9][0-9]))|((30[8-9][8-9][0-9][0-9])|309[0-4][0-9][0-9])|((309[6-9][0-9][0-9])|310[0-2][0-9][0-9])|(311[2-9][0-9][0-9])|(3120[0-9][0-9])|(315[8-9][0-9][0-9])|((333[7-9][0-9][0-9])|(334[0-9][0-9][0-9])))/,  // NOSONAR
            cnLength: [16, 17, 18, 19],
        },
        {
            cbType: 'unionpay',
            pattern: /^(62|9558|81)/,
            cnLength: [13, 14, 15, 16, 17, 18, 19],
        },
        {
            cbType: 'elo',
            pattern: /^(50(67(0[78]|1[5789]|2[012456789]|3[01234569]|4[0-7]|53|7[4-8])|9(0(0[0123478]|14|2[0-2]|3[359]|4[01235678]|5[1-9]|6[0-9]|7[0134789]|8[04789]|9[12349])|1(0[34568]|4[6-9]|83)|2(20|5[7-9]|6[0-6])|4(0[7-9]|1[0-2]|31)|7(22|6[5-9])))|4(0117[89]|3(1274|8935)|5(1416|7(393|63[12])))|6(27780|36368|5(0(0(3[12356789]|4[0-9]|5[01789]|6[01345678]|7[78])|4(0[6-9]|1[0-3]|2[2-6]|3[4-9]|8[5-9]|9[0-9])|5(0[012346789]|1[0-9]|2[0-9]|3[0-8]|7[7-9]|8[0-9]|9[0-8])|72[0-7]|9(0[1-9]|1[0-9]|2[0128]|3[89]|4[6-9]|5[045]|6[25678]|71))|16(5[2-9]|6[0-9]|7[01456789])|50(0[0-9]|1[0-9]|2[1-9]|3[0-6]|5[1-7]))))/, // NOSONAR
            cnLength: [13, 16, 19],
        },
        {
            cbType: 'mastercard',
            pattern: /^5[1-5]|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))/,
            cnLength: [16],
        },
        {
            cbType: 'maestro',
            pattern: /^(0604|50|5[6789]|60|61|63|64|67|6660|6670|6818|6858|6890|6901|6907)/,
            cnLength: [12, 13, 14, 15, 16, 17, 18, 19],
        },
    ];

    const cardNumberError = jQuery('#ul-card-number-error');
    const isCardNumberValid = validateCardNumber(CARD_BRANDS, cardNumberError);

    cardNumberError.hide();
    if (!isCardNumberValid) {
        cardNumberError.show();
    }

    return isCardNumberValid;
}

const formatUlCvc = function () {
    const cvc = jQuery('#ul-cvc');

    const cvcFormatted = cvc.val().replace(/\D/g, '')
        .replace(/^(\d{4})(\d)/g, "$1 $2")
        .replace(/^(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3")
        .replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, "$1 $2 $3 $4");

    cvc.val(cvcFormatted);
}

const isUlCvcValid = function () {
    const cvc = jQuery('#ul-cvc');

    if (!cvc.val() || cvc.val().length <= 2) {
        return false;
    }

    return /\S/.test(cvc.val());
}

const formatUlExpirationDate = function () {
    const date = jQuery('#ul-card-expiration-date');

    const dateFormatted = date.val().replace(/\D/g, '')
        .replace(/^(0\d|1[0-2])\/?(\d{2})$/, "$1/$2")
        .replace(/(\d{2})(\d{2})$/, "$1$2");

    date.val(dateFormatted);
}

const validateCardNumber = function (CARD_BRANDS, cardNumberError) {
    const cardBrandSpan = jQuery('#card-brand');
    cardBrandSpan.removeAttr('class');

    const cardNumberInputField = jQuery('#ul-card-number');
    if (!cardNumberInputField) {
        return true;
    }
    cardNumberInputField.removeClass(UL_ERROR_CLASS);

    const cardNumber = cardNumberInputField.val().replace(/[^\d]/gi, '');

    let isCardNumberValid = true;
    for (let cardBrandIndex = 0; cardBrandIndex <= CARD_BRANDS.length - 1; cardBrandIndex++) {
        const cardBrand = CARD_BRANDS[cardBrandIndex];
        if (cardBrand.pattern.test(cardNumber)) {
            if (cardBrand.cbType === 'unionpay') {
                if (!cardBrand.cnLength.includes(cardNumber.length) || cardNumber.length < 13 || cardNumber.length > 19) {
                    isCardNumberValid = false;
                    cardNumberError.show();
                }

                cardBrandSpan.addClass('card-brand-' + cardBrand.cbType);
                return isCardNumberValid;
            }

            if (!cardBrand.cnLength.includes(cardNumber.length) || !isUlLuhnAlgorithmPassed(cardNumber)) {
                isCardNumberValid = false;
            }

            cardBrandSpan.addClass('card-brand-' + cardBrand.cbType);
            break;
        }
    }

    // unknown card brand
    if (cardNumber.length < 13 || cardNumber.length > 19 || !isUlLuhnAlgorithmPassed(cardNumber)) {
        isCardNumberValid = false;
    }

    return isCardNumberValid;
}

const isUlExpirationDateValid = function (expirationDate) {
    if (!isUlExpirationDateSet(expirationDate)) {
        return false;
    }

    const expirationValues = expirationDate.split('/');
    const expirationMonth = parseInt(expirationValues[0]);
    if (expirationMonth < 1 || expirationMonth > 12) {
        return false;
    }

    const expirationYear = parseInt(20 + expirationValues[1]);

    const currentTime = new Date()
    const currentYear = currentTime.getFullYear();
    const currentMonth = currentTime.getMonth() + 1;

    return !(expirationYear < currentYear
        || (expirationYear > currentYear + UL_MAX_CARD_EXPIRATION_YEARS)
        || (expirationYear === currentYear && expirationMonth < currentMonth));
}

const isUlExpirationDateSet = function (expirationDate) {
    if (!expirationDate) {
        return false;
    }

    const expirationValues = expirationDate.split('/');
    return !(typeof expirationValues[0] === 'undefined' || typeof expirationValues[1] === 'undefined');
}

const areUlInstallmentsValid = function () {
    const installments = jQuery('#ul-installments');
    if (!installments) {
        return true;
    }

    return installments.val();
}

const validateUlCardFieldInput = function (field) {
    setTimeout(validateUlCardField(field.id), 1);
}
