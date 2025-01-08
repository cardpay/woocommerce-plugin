function autoFillCountryCode(phoneField, countryCode) {
    const value = phoneField.val().trim().replace(/\s+/g, '');

    if (value === "+") {
        phoneField.val(countryCode);
    } else if (value.startsWith(countryCode)) {
        console.debug('Value starts with the correct country code');
    } else if (value.startsWith(countryCode.slice(1))) {
        phoneField.val('+' + value);
    } else if (value.startsWith('+')) {
        phoneField.val(countryCode + value.slice(value.indexOf(countryCode.slice(1))));
    } else {
        phoneField.val(countryCode + value);
    }
}

function validatePhoneNumber(phoneFieldSelector, phoneErrFieldSelector, phoneErrFieldSecondSelector, phonePattern, minLength, maxLength) {
    const phoneField = jQuery(phoneFieldSelector);
    const phoneErrField = jQuery(phoneErrFieldSelector);
    const phoneErrFieldSecond = jQuery(phoneErrFieldSecondSelector);

    phoneField.val(phoneField.val().replace(/[^+\d]/g, '').trim());
    const phoneNumber = phoneField.val();

    const showError = (errorField) => {
        phoneErrField.hide();
        phoneErrFieldSecond.hide();
        errorField.show();
        phoneField.addClass(UL_ERROR_CLASS);
    };

    if (phoneNumber === '') {
        showError(phoneErrFieldSecond);
        return false;
    }

    if (phoneNumber.length < minLength || phoneNumber.length > maxLength || !phonePattern.test(phoneNumber)) {
        showError(phoneErrField);
        return false;
    }

    phoneField.removeClass(UL_ERROR_CLASS);
    phoneErrField.hide();
    phoneErrFieldSecond.hide();
    return true;
}
