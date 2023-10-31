let isModalOpen = false;

const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
};

const allowedCardNetworks = ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"];

let allowedCardAuthMethods = ['PAN_ONLY', 'CRYPTOGRAM_3DS'];

const tokenizationSpecification = (gateway, gatewayMerchantId) => {
    return {
        type: 'PAYMENT_GATEWAY',
        parameters: {
            'gateway': gateway,
            'gatewayMerchantId': gatewayMerchantId.toString(),
        },
    };
};

const baseCardPaymentMethod = {
    type: 'CARD',
    parameters: {
        allowedAuthMethods: allowedCardAuthMethods,
        allowedCardNetworks: allowedCardNetworks,
    },
};

const cardPaymentMethod = (gateway, gatewayMerchantId) => Object.assign(
    {},
    baseCardPaymentMethod,
    {
        tokenizationSpecification: tokenizationSpecification(gateway, gatewayMerchantId),
    },
);

let paymentsClient = null;

function getGoogleIsReadyToPayRequest() {
    return Object.assign(
        {},
        baseRequest,
        {
            allowedPaymentMethods: [baseCardPaymentMethod],
        },
    );
}

function getGooglePaymentDataRequest() {
    const gatewayMerchantId = document.getElementById('container').value.split(' ');

    const paymentDataRequest = Object.assign({}, baseRequest);
    paymentDataRequest.allowedPaymentMethods = [
        cardPaymentMethod('unlimint', gatewayMerchantId[0]),
    ];
    paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
    paymentDataRequest.merchantInfo = {
        merchantName: 'Unlimint',
    };

    paymentDataRequest.callbackIntents = ['PAYMENT_AUTHORIZATION'];

    return paymentDataRequest;
}

function createPaymentsClient(env = 'TEST') {
    if (paymentsClient === null) {
        paymentsClient = new google.payments.api.PaymentsClient({
            environment: env,
            paymentDataCallbacks: {
                onPaymentAuthorized: onPaymentAuthorized,
            },
        });
    }
    return paymentsClient;
}

function onPaymentAuthorized(paymentData) {
    return new Promise(function (resolve, reject) {
        processPayment(paymentData).then(function (e) {
            const placeOrderButton = document.getElementById('place_order');
            placeOrderButton.click();
            resolve({transactionState: 'SUCCESS'});
        }).catch(function (e) {
            resolve({
                transactionState: 'ERROR',
                error: {
                    intent: 'PAYMENT_AUTHORIZATION',
                    message: 'Insufficient funds, try again. Next attempt should work.',
                    reason: 'PAYMENT_DATA_INVALID',
                },
            });
        });
    });
}

function onGooglePayLoaded() {
    const getPaymentsClient = createPaymentsClient();
    getPaymentsClient.isReadyToPay(getGoogleIsReadyToPayRequest()).then(function (response) {
        if (response.result) {
            addGooglePayButton();
        }
    }).catch(function (err) {
        // show error in developer console for debugging
        console.error(err);
    });
}

function addGooglePayButton() {
    const existingPaymentsClient = createPaymentsClient();
    const button = existingPaymentsClient.createButton(
        {onClick: onGooglePaymentButtonClicked});
    document.getElementById('buttonContainer').appendChild(button);
}

function onChangeEnvironment(selectedOption) {
    paymentsClient = null;
    document.querySelector('div#buttonContainer div').remove();
    createPaymentsClient(selectedOption.value);
    onGooglePayLoaded();
}

function onChangeAuthMethod(selectedOption) {
    document.querySelector('div#buttonContainer div').remove();
    allowedCardAuthMethods = selectedOption.value.split(',');
    onGooglePayLoaded();
}

function getGoogleTransactionInfo() {
    const totalElement = document.querySelector('.order-total .woocommerce-Price-amount');
    const totalValueText = totalElement.textContent.trim();
    const totalValue = totalValueText.match(/[\d.-]+/)[0];
    const parameters = document.getElementById('container').value.split(' ');

    return {
        currencyCode: parameters[1],
        totalPriceStatus: 'FINAL',
        totalPrice: totalValue,
    };
}

function onGooglePaymentButtonClicked() {
    const paymentDataRequest = getGooglePaymentDataRequest();
    paymentDataRequest.transactionInfo = getGoogleTransactionInfo();

    const newPaymentsClient = createPaymentsClient();
    newPaymentsClient.loadPaymentData(paymentDataRequest)
        .then(function (paymentData) {
            console.log(paymentData)
            // handle the response
            processPayment(paymentData);
        })
        .catch(function (err) {
            if (err.statusCode === "CANCELED") {
                isModalOpen = false;
            }
        });
}

const attempts = 0;

function processPayment(paymentData) {
    return new Promise(function (resolve, reject) {
        setTimeout(function () {
            var paymentToken = paymentData.paymentMethodData.tokenizationData.token;
            jQuery('#co-cardpay-form-gpay').find('[name="cardpay_custom_gpay[signature]"]').val(paymentToken);
            resolve({});
        }, 500);
    }, 500);
}
