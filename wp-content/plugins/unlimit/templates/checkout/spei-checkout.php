<?php

defined( 'ABSPATH' ) || exit;

$speiLogoUrl = plugins_url( '../../assets/images/spei.png', __FILE__ );
?>

<div id="unlimit_spei_modal_bg" class="unlimit_modal_bg closed_unlimit">
    <div id="unlimit_spei_modal_page" name="unlimit_spei_modal_page" class="unlimit_modal_page">
        <iframe id="unlimit_spei_modal_iframe" class="unlimit_modal_iframe"
                width="100%" height="100%" title="unlimit_spei_modal_iframe"></iframe>
    </div>
</div>
<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimit-form-spei'>
                    <div id='form-spei'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8'>
                                <img src='<?php echo $speiLogoUrl ?>' width='99' height='35' alt='Spei'/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ( $is_spei_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("spei");
    </script>
	<?php
}