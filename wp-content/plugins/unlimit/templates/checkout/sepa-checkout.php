<?php

defined( 'ABSPATH' ) || exit;

$sepaLogoUrl = plugins_url( '../../assets/images/sepa.png', __FILE__ );
?>

    <div id="unlimit_sepa_modal_bg" class="unlimit_modal_bg closed_unlimit">
        <div id="unlimit_sepa_modal_page" name="unlimit_sepa_modal_page" class="unlimit_modal_page">
            <iframe id="unlimit_sepa_modal_iframe" class="unlimit_modal_iframe"
                    width="100%" height="100%" title="unlimit_seps_modal_iframe"></iframe>
        </div>
    </div>
    <div class='ul-panel-custom-checkout'>
        <div class='ul-row-checkout'>
            <div class='ul-col-md-12'>
                <div class='frame-tarjetas'>
                    <div id='unlimit-form-sepa'>
                        <div id='form-sepa'>
                            <div class='ul-row-checkout'>
                                <div class='ul-col-md-8' style="10px 0 0 0">
                                    <img src='<?php echo $sepaLogoUrl ?>' width='99' height='35' alt='Sepa'/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if ( $is_sepa_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("sepa");
    </script>
	<?php
}