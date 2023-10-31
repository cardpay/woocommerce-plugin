<?php

defined( 'ABSPATH' ) || exit;

$mbwayLogoUrl = plugins_url( '../../assets/images/mbway.png', __FILE__ );
?>

<div id="unlimit_mbway_modal_bg" class="unlimit_modal_bg closed_unlimit">
    <div id="unlimit_mbway_modal_page" name="unlimit_mbway_modal_page" class="unlimit_modal_page">
        <iframe id="unlimit_mbway_modal_iframe" class="unlimit_modal_iframe" width="100%" height="100%"
                title="unlimit_mbway_modal_iframe"></iframe>
    </div>
</div>
<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimit-form-mbway'>
                    <div id='form-mbway'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8'>
                                <label for='ul-cpf-ticket' id='ul-cpf-label'
                                       class='ul-label-form title-phone'>
                                    <?php echo esc_html__( 'MB WAY phone number', 'unlimit' ); ?><em>*</em>
                                </label>
                                <input type='text'
                                       onfocusout="validateUlMbwayInput();"
                                       class='ul-form-control'
                                       id='ul-mbway-phone' data-checkout='ul-mbway-phone'
                                       name='cardpay_mbway[phone_number]'><br/>
                                <span class="ul-error ul-mt-5" id="ul-mbway-phone-error"
                                      data-main="#ul-mbway-phone-error">
                                <?php echo esc_html__( 'MB WAY phone number is invalid', 'unlimit' ); ?>
                            </span>
                                <span class="ul-error ul-mt-5" id="ul-mbway-phone-error-second"
                                      data-main="#ul-mbway-phone-error">
                                <?php echo esc_html__( 'Please fill out a MB WAY phone number', 'unlimit' ); ?>
                            </span><br>
                                <img src='<?php echo $mbwayLogoUrl ?>' width='70' height='65' alt='MB WAY'/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ( $is_mbway_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("mbway");
    </script>
	<?php
}