<?php

defined( 'ABSPATH' ) || exit;

$apayBrandsLogoUrl = plugins_url( '../../assets/images/gpay.png', __FILE__ );
?>

<div class="ul-panel-custom-checkout">
    <div class="ul-row-checkout" id="co-cardpay-form-apay" style="display: none">
        <div class="ul-col-md-12">
            <div class="frame-tarjetas">
                <div id="unlimit-form" style="display: none">
                    <div class="ul-row-checkout">
                        <div class="mp-box-inputs mp-col-100" id="buttonContainer">
                            <input id="container" name="cardpay_custom_apay[signature]"
                                   value="<?php echo $apple_merchant_id ?>" style="display: none"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="padding: 10px 0 20px 0;">
        <div id="apple-pay-button" class="apple-pay input-block-level d-none"></div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        if (unlimit.applePay.supportedByDevice()) {
            console.log('Is ApplePaySession available in the browser? Yes')
            unlimit.applePay.showButton();
        } else {
            let msgApplePayFailed = 'This device and/or browser does not support Apple Pay.'
            console.log(msgApplePayFailed);
            unlimit.applePay.showError(msgApplePayFailed);
        }
    });
</script>