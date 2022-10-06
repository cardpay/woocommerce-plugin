<?php

defined( 'ABSPATH' ) || exit;

$cardBrandsLogoUrl = plugins_url( '../../assets/images/card_brands.png', __FILE__ );

$currency_symbol = '';
if ( isset( $installment_options['currency'] ) ) {
	$currency_symbol = $installment_options['currency'];
}
$display_form = ( $is_payment_page_required && ( ! $are_installments_enabled ) ) ? 'none' : 'block';
if ( $display_form === 'none' ) {
	?>
    <style>
        .payment_box.payment_method_woo-unlimint-custom {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
	<?php
}
?>
    <div id="unlimint_modal_bg" class="closed">
        <div id="unlimint_modal_page" name="unlimint_modal_page">
            <iframe id="unimint_modal_iframe" width="100%" height="100%" title="unimint_modal_iframe"></iframe>
        </div>
    </div>
    <div class="ul-panel-custom-checkout" style="display: <?php echo $display_form; ?>">
        <div class="ul-row-checkout">
            <div class="ul-col-md-12">
                <div class="frame-tarjetas">
                    <div id="unlimint-form">
						<?php if ( ! $is_payment_page_required ) { ?>
                            <div class="ul-row-checkout">
                                <div class="ul-col-md-12" style="position: relative;">
                                    <label for="ul-card-number"
                                           class="ul-label-form"><?php echo esc_html__( 'Card number', 'unlimint' ); ?>
                                        <em>*</em></label>
                                    <input type="text" onkeyup="formatUlCardField(this.id);"
                                           onfocusout="validateUlCardFieldInput(this);"
                                           class="ul-form-control ul-mt-5"
                                           autocomplete="off" minlength="13" maxlength="19"
                                           name="unlimint_custom[cardNumber]" id="ul-card-number"
                                           data-checkout="cardNumber"/>
                                    <span id="card-brand" class="card-brand"></span>
                                    <span class="ul-error ul-mt-5" id="ul-card-number-error"
                                          data-main="#ul-card-number-error"><?php echo esc_html__( 'Card number is not valid', 'unlimint' ); ?></span>
                                    <span class="ul-error ul-mt-5" id="ul-card-number-error-second"
                                          data-main="#ul-card-number-error"><?php echo esc_html__( 'Please fill out card number', 'unlimint' ); ?></span>
                                </div>
                            </div>

                            <div class="ul-row-checkout ul-pt-10" id="ul-card-holder-div">
                                <div class="ul-col-md-12">
                                    <label for="ul-card-holder-name"
                                           class="ul-label-form"><?php echo esc_html__( 'Cardholder name', 'unlimint' ); ?>
                                        <em>*</em></label>
                                    <input type="text" class="ul-form-control ul-mt-5" autocomplete="off" minlength="2"
                                           maxlength="50"
                                           onkeyup="formatUlCardField(this.id);"
                                           onfocusout="validateUlCardFieldInput(this);"
                                           id="ul-card-holder-name" data-checkout="ul-card-holder-name"
                                           name="unlimint_custom[cardholderName]"/>

                                    <span class="ul-error ul-mt-5" id="ul-card-holder-name-error"
                                          data-main="#ul-card-holder-name-error"><?php echo esc_html__( 'Card holder name is not valid', 'unlimint' ); ?></span>
                                    <span class="ul-error ul-mt-5" id="ul-card-holder-name-error-second"
                                          data-main="#ul-card-holder-name-error"><?php echo esc_html__( 'Please fill out card holder name', 'unlimint' ); ?></span>
                                </div>
                            </div>

                            <div class="ul-row-checkout ul-pt-10">
                                <!-- Input expiration date -->
                                <div class="ul-col-md-6 ul-pr-15">
                                    <label for="ul-card-expiration-date"
                                           class="ul-label-form"><?php echo esc_html__( 'Expiration date', 'unlimint' ); ?>
                                        <em>*</em></label>
                                    <input type="text" onkeyup="formatUlCardField(this.id);"
                                           onfocusout="validateUlCardFieldInput(this);"
                                           class="ul-form-control ul-mt-5" autocomplete="off" placeholder="MM/YY"
                                           maxlength="5" name="unlimint_custom[cardExpirationDate]"
                                           id="ul-card-expiration-date" data-checkout="cardExpirationDate"
                                    />
                                    <span class="ul-error ul-mt-5" id="ul-card-expiration-date-error"
                                          data-main="#ul-card-expiration-date-error"><?php echo esc_html__( 'Invalid expiration date', 'unlimint' ); ?></span>
                                    <span class="ul-error ul-mt-5" id="ul-card-expiration-date-error-second"
                                          data-main="#ul-card-expiration-date-error"><?php echo esc_html__( 'Please fill out an expiration date', 'unlimint' ); ?></span>
                                </div>

                                <div class="ul-col-md-6">
                                    <label for="ul-cvc"
                                           class="ul-label-form"><?php echo esc_html__( 'CVV2/CVC2', 'unlimint' ); ?>
                                        <em>*</em></label>
                                    <input type="password" onkeyup="formatUlCardField(this.id);"
                                           onfocusout="validateUlCardFieldInput(this);"
                                           class="ul-form-control ul-mt-5" autocomplete="off" minlength="3"
                                           maxlength="4" name="unlimint_custom[cvc]" id="ul-cvc" data-checkout="cvc"/>
                                    <span class="ul-error ul-mt-5" id="ul-cvc-error"
                                          data-main="#ul-cvc-error"><?php echo esc_html__( 'This CVV2/CVC2 is not valid', 'unlimint' ); ?></span>
                                    <span class="ul-error ul-mt-5" id="ul-cvc-error-second"
                                          data-main="#ul-cvc-error"><?php echo esc_html__( 'Please fill out a CVV2/CVC2', 'unlimint' ); ?></span>
                                </div>
                            </div>
						<?php } ?>
						<?php if ( $are_installments_enabled ) { ?>
                            <div class="ul-col-md-12">
                                <div class="frame-tarjetas">
                                    <div class="ul-row-checkout ul-pt-10">
                                        <div id="installments-div" class="ul-col-md-12">
                                            <label for="ul-installments" class="ul-label-form">
												<?php echo esc_html__( 'Installments', 'unlimint' ); ?>
                                                <em>*</em></label>

                                            <select class="ul-form-control ul-pointer ul-mt-5" id="ul-installments"
                                                    onkeyup="formatUlCardField(this.id);"
                                                    onfocusout="validateUlCardFieldInput(this);"
                                                    onchange="if (this.selectedIndex) validateUlCardFieldInput(this);"
                                                    data-checkout="installments" name="unlimint_custom[installments]">
												<?php
												if ( ! empty( $currency_symbol ) && isset( $installment_options['options'] ) ) {
													$first_option = esc_html__( 'Select number of installments', 'unlimint' );
													echo "<option value=''>$first_option</option>";

													foreach ( $installment_options['options'] as $option ) {
														if ( ! isset( $option['installments'], $option['amount'] ) ) {
															continue;
														}

														$installments = $option['installments'];
														$amount       = $option['amount'];
														echo "<option value='$installments'>$installments x $currency_symbol$amount</option>";
													}
												}
												?>
                                            </select>
                                            <span class="ul-error ul-mt-5" id="ul-installments-error-second"
                                                  data-main="#ul-installments-error"><?php echo esc_html__( 'Please select number of installments', 'unlimint' ); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>
						<?php if ( $is_cpf_required ) { ?>
							<?php if ( ! $is_payment_page_required ) { ?>
                                <div id="form-common">
                                    <div class="ul-row-checkout ul-pt-10">
                                        <div class="ul-col-md-8" id="box-cpf">
                                            <label for="ul-cpf" id="ul-cpf-label"
                                                   class="ul-label-form title-cpf"><?php echo esc_html__( 'CPF', 'unlimint' ); ?>
                                                <em>*</em></label>
                                            <input type="text" onkeyup="formatUlCardField(this.id);"
                                                   onfocusout="validateUlCardFieldInput(this);"
                                                   class="ul-form-control" id="ul-cpf"
                                                   data-checkout="ul-cpf" name="unlimint_custom[cpf]"
                                                   autocomplete="off" maxlength="14"
                                                   placeholder="XXX.XXX.XXX-XX">
                                            <span class="ul-error ul-mt-5" id="ul-cpf-error"
                                                  data-main="#ul-cpf-error"><?php echo esc_html__( 'CPF is invalid', 'unlimint' ); ?></span>
                                            <span class="ul-error ul-mt-5" id="ul-cpf-error-second"
                                                  data-main="#ul-cpf-error"><?php echo esc_html__( 'Please fill out a CPF', 'unlimint' ); ?></span>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>
						<?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding: 20px 0 20px 0;">
            <img src="<?php echo $cardBrandsLogoUrl ?>" width="109" height="35" alt="Credit card brands"/>
        </div>
    </div>
<?php if ( $is_payment_page_required ) { ?>
    <script>
        jQuery(document).ready(function () {
            unlimintIframeProcessor.setModalSize();
            jQuery(window).resize(function () {
                unlimintIframeProcessor.setModalSize();
            });
            jQuery('#place_order').click(function () {
                unlimintIframeProcessor.buttonClick();
            });
        });
    </script>
	<?php
}
