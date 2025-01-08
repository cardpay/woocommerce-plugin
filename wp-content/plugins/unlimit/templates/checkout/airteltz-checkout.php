<?php

defined( 'ABSPATH' ) || exit;

$airteltzLogoUrl = plugins_url( '../../assets/images/airteltz.png', __FILE__ );
?>

    <div id="unlimit_airteltz_modal_bg" class="unlimit_modal_bg closed_unlimit">
        <div id="unlimit_airteltz_modal_page" name="unlimit_airteltz_modal_page" class="unlimit_modal_page">
            <iframe id="unlimit_airteltz_modal_iframe" class="unlimit_modal_iframe" width="100%" height="100%"
                    title="unlimit_airteltz_modal_iframe"></iframe>
        </div>
    </div>
    <div class='ul-panel-custom-checkout'>
        <div class='ul-row-checkout'>
            <div class='ul-col-md-12'>
                <div class='frame-tarjetas'>
                    <div id='unlimit-form-airteltz'>
                        <div id='form-airteltz'>
                            <div class='ul-row-checkout'>
                                <div class='ul-col-md-8'>
                                    <label for='ul-cpf-ticket' id='ul-cpf-label'
                                           class='ul-label-form title-phone'>
										<?php
										echo esc_html__( 'Airtel TZ phone number', 'unlimit' ); ?><em>*</em>
                                    </label>
                                    <div style="position: relative" class="ul-mt-5">
                                    <input type='text'
                                           onfocusout="validateUlAirteltzInput();"
                                           class='ul-form-control'
                                           id='ul-airteltz-phone' data-checkout='ul-airteltz-phone'
                                           name='cardpay_airteltz[phone_number]'>
                                    </div>
                                    <span class="ul-error ul-mt-5" id="ul-airteltz-phone-error"
                                          data-main="#ul-airteltz-phone-error">
                                <?php
                                echo esc_html__( 'Airtel TZ phone number is invalid', 'unlimit' ); ?>
                            </span>
                                    <span class="ul-error ul-mt-5" id="ul-airteltz-phone-error-second"
                                          data-main="#ul-airteltz-phone-error">
                                <?php
                                echo esc_html__( 'Please fill out an Airtel TZ phone number', 'unlimit' ); ?>
                            </span>
                                    <img src='<?php
									echo $airteltzLogoUrl ?>' width='70' height='65' alt='airteltz' style="margin-top: 30px"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
if ( $is_airteltz_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("airteltz");
    </script>
	<?php
}