function setupPaymentForm(idPrefix) {
    jQuery('#mainform').submit(function (e) {
        const prefix = idPrefix;
        if (!jQuery(`#${prefix}terminal_code`).length) {
            return;
        }
        validateAltMethodForm(prefix, e);
    });
    jQuery(`#${idPrefix}payment_page`).change(function () {
        if (typeof API_MODE_CHANGE_WARNING_CHANGE_MODE !== 'undefined') {
            alert(API_MODE_CHANGE_WARNING_CHANGE_MODE);
        } else {
            alert('API access mode is changed, please check Terminal code, Terminal password, Callback secret values.' +
                ' After changing the API mode in the plugin, API access mode in Unlimit must also be changed.' +
                ' Please consult about it with Unlimit support.');
        }
    });
}
