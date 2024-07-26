<?php

defined( 'ABSPATH' ) || exit;

$oxxoLogoUrl = plugins_url( '../../assets/images/oxxo.png', __FILE__ );
?>

    <div id="unlimit_oxxo_modal_bg" class="unlimit_modal_bg closed_unlimit">
        <div id="unlimit_oxxo_modal_page" name="unlimit_oxxo_modal_page" class="unlimit_modal_page">
            <iframe id="unlimit_oxxo_modal_iframe" class="unlimit_modal_iframe"
                    width="100%" height="100%" title="unlimit_oxxo_modal_iframe"></iframe>
        </div>
    </div>
    <div class='ul-panel-custom-checkout'>
        <div class='ul-row-checkout'>
            <div class='ul-col-md-12'>
                <div class='frame-tarjetas'>
                    <div id='unlimit-form-oxxo'>
                        <div id='form-oxxo'>
                            <div class='ul-row-checkout'>
                                <div class='ul-col-md-8' style="padding: 10px 0 0 0">
                                    <img src='<?php echo $oxxoLogoUrl ?>' width='99' height='35' alt='Oxxo'/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if ( $is_oxxo_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("oxxo");
    </script>
	<?php
}