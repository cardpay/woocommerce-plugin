function setupPaymentForm(idPrefix) {
    jQuery('#mainform').submit(function (e) {
        const prefix = idPrefix;
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }
        validateAltMethodForm(prefix, e);
    });
    jQuery(`#${idPrefix}payment_page`).change(function () {
        alert(unlimit_vars.bankcard_translations.api_mode_change_warning);
    });
}
