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
