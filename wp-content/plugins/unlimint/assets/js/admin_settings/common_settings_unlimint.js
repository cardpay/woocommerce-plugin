const validateUlAdminField = function (fieldName, maxLength, errorField, positiveInteger) {
    const errorMessageId = fieldName + '-error';
    const errorMessageField = jQuery(`[id=${errorMessageId}]`);
    if (errorMessageField) {
        errorMessageField.remove();
    }

    const adminField = jQuery(`#${fieldName}`);
    if (adminField) {
        const fieldValue = adminField.val();
        if (!fieldValue || fieldValue.trim().length === 0) {
            showUlAdminError(errorMessageId, `Empty ${errorField}`);
            return false;
        }

        if (fieldValue.length > maxLength || (positiveInteger && (isNaN(fieldValue) || parseInt(fieldValue) < 0))) {
            showUlAdminError(errorMessageId, `Invalid ${errorField}`);
            return false;
        }
    }

    return true;
}

const showUlAdminError = function (errorMessageId, errorMessage) {
    jQuery(`<div class='error inline' id='${errorMessageId}'><p>${errorMessage}</p></div>`).insertBefore('table.form-table');
}

const validateAltMethodForm = function (prefix, e) {
    const isValidTerminalCode = validateUlAdminField(prefix + 'terminal_code', 128, 'terminal code', true);
    const isValidTerminalPassword = validateUlAdminField(prefix + 'terminal_password', 128, 'terminal password', false);
    const isValidCallbackSecret = validateUlAdminField(prefix + 'callback_secret', 128, 'callback secret', false);
    const isValidPaymentTitle = validateUlAdminField(prefix + 'payment_title', 128, 'payment title', false);

    if (!isValidTerminalCode || !isValidTerminalPassword || !isValidCallbackSecret || !isValidPaymentTitle) {
        e.preventDefault(e);
    }
}
