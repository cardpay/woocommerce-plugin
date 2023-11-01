<?php

defined( 'ABSPATH' ) || exit;

$paypalLogoUrl = plugins_url( '../../assets/images/paypal.png', __FILE__ );
?>

    <div id="unlimit_paypal_modal_bg" class="unlimit_modal_bg closed_unlimit">
        <div id="unlimit_paypal_modal_page" name="unlimit_paypal_modal_page" class="unlimit_modal_page">
            <iframe id="unlimit_paypal_modal_iframe" class="unlimit_modal_iframe" width="100%" height="100%"
                    title="unlimit_paypal_modal_iframe"></iframe>
        </div>
    </div>
    <div class='ul-panel-custom-checkout'>
        <div class='ul-row-checkout unlimit-custom-padding'>
            <div class='ul-col-md-12'>
                <div class='frame-tarjetas'>
                    <div id='unlimit-form-paypal'>
                        <div id='form-paypal'>
                            <div class='ul-row-checkout'>
                                <div class='ul-col-md-8'>
                                    <img src='<?php echo $paypalLogoUrl ?>' width='99' height='35' alt='Paypal'/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if ( $is_paypal_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("paypal");
    </script>
	<?php
}
