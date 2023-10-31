<?php

defined( 'ABSPATH' ) || exit;

$multibancoLogoUrl = plugins_url( '../../assets/images/multibanco.png', __FILE__ );
?>

<div id="unlimit_multibanco_modal_bg" class="unlimit_modal_bg closed_unlimit">
    <div id="unlimit_multibanco_modal_page" name="unlimit_multibanco_modal_page" class="unlimit_modal_page">
        <iframe id="unlimit_multibanco_modal_iframe" class="unlimit_modal_iframe" width="100%" height="100%"
                title="unlimit_multibanco_modal_iframe"></iframe>
    </div>
</div>
<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimit-form-multibanco'>
                    <div id='form-multibanco'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8'>
                                <img src='<?php echo $multibancoLogoUrl ?>' width='99' height='35'
                                     alt='Multibanco'/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ( $is_multibanco_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("multibanco");
    </script>
	<?php
}