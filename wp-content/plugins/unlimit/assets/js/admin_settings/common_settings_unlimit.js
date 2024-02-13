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
            showUlAdminError(errorMessageId, adminField.data('empty-error'));
            highlightUlAdminError(fieldName);
            return false;
        }

        if (fieldValue.length > maxLength || (positiveInteger && (isNaN(fieldValue) || parseInt(fieldValue) < 0))) {
            showUlAdminError(errorMessageId, adminField.data('invalid-error'));
            highlightUlAdminError(fieldName);
            return false;
        }
    }
    hideUlAdminError(fieldName);
    return true;
}

const hideUlAdminError = function (id) {
    jQuery(`#${id}`).parent().parent().parent().removeClass('ul_error');
}

const highlightUlAdminError = function (id) {
    jQuery(`#${id}`).parent().parent().parent().addClass('ul_error');
}

const showUlAdminError = function (errorMessageId, errorMessage) {
    jQuery(`<div class='error inline' id='${errorMessageId}'><p>${errorMessage}</p></div>`).insertBefore('table.form-table');
}

const validateAltMethodForm = function (prefix, e) {
    const isValidTerminalCode = validateUlAdminField(prefix + 'terminal_code', 128, 'Terminal code', true);
    const isValidTerminalPassword = validateUlAdminField(prefix + 'terminal_password', 128, 'Terminal password', false);
    const isValidCallbackSecret = validateUlAdminField(prefix + 'callback_secret', 128, 'Callback secret', false);
    const isValidPaymentTitle = validateUlAdminField(prefix + 'payment_title', 128, 'Payment title', false);

    if (!isValidTerminalCode || !isValidTerminalPassword || !isValidCallbackSecret || !isValidPaymentTitle) {
        e.preventDefault(e);
    }
}
