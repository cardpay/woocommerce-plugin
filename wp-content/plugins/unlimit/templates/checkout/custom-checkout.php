<?php

defined( 'ABSPATH' ) || exit;

$imgDir            = '../../assets/images/';
$cardBrandsLogoUrl = plugins_url( $imgDir . 'card_brands.png', __FILE__ );

$currency_symbol = '';
if ( isset( $installment_options['currency'] ) ) {
	$currency_symbol = $installment_options['currency'];
}
$display_form = (
	$is_payment_page_required &&
	( ! $are_installments_enabled ||
	  ( $is_payment_page_required && $is_recurring_enabled )
	)
) ? 'block' : 'none';

if ( $display_form === 'none' ) {
	?>
    <style>
        .payment_box.payment_method_woo-unlimit-custom {
            display: none;
        }
    </style>
	<?php
}
?>

    <div id="unlimit_modal_bg" class="unlimit_modal_bg closed_unlimit">
        <div id="unlimit_modal_page" name="unlimit_modal_page" class="unlimit_modal_page">
            <iframe id="unlimit_modal_iframe" class="unlimit_modal_iframe"
                    width="100%" height="100%" title="unlimit_modal_iframe"></iframe>
        </div>
    </div>
    <div class="ul-panel-custom-checkout custom-width">
        <div class="ul-row-checkout row width-100" style="margin: 0 auto">
            <div class="col-md-12 width-100">
                <div class="frame-tarjetas">
                    <div class="saved_cards">
						<?php
						if ( $existing_filing_ids && $is_recurring_enabled && ! $are_installments_enabled ) { ?>
                            <div class="woocommerce-message unlimit-custom-success"
                                 role="alert" style="display: none;"></div>
                            <div class="woocommerce-error unlimit-custom-error"
                                 role="alert" style="display: none;"></div>
                            <label><span><?php
									__( 'Saved cards', 'unlimit' ); ?></span></label>
                            <div class="select_filing_id">
								<?php
								foreach ( $existing_filing_ids as $card ) { ?>
                                    <div class="filing_id_wrapper">
                                        <div class="card-details">
                                            <input type="radio" name="unlimit_custom[filing_id]"
                                                   class="input-radio ul-mt-5"
                                                   value="<?php
											       echo $card['recurring_data_id'] ?>" style="margin: 0"/>
                                            <span id="card-log-recurring" class="card-logo card-brand-<?php
											echo $card['card_type'] ?>"></span>
											<?php
											echo $card['masked_pan'] ?>
                                        </div>
                                        <div class="delete-card-wrapper" style="display: none;">
                                            <p style="font-size: 13px; text-align: center">
												<?php
												echo esc_html__( 'Do you want to delete the card?', 'unlimit' ); ?>
                                            </p>
                                            <button class="delete-cancel" type="button" style="--max-ch: 8.4;">
												<?php
												echo esc_html__( 'Cancel', 'unlimit' ); ?>
                                            </button>
                                            <button class="delete-card" type="button"
                                                    value="<?php
											        echo $card['recurring_data_id'] ?>"
                                                    style="--max-ch: 7.3; float: right">
												<?php
												echo esc_html__( 'Delete', 'unlimit' ); ?>
                                            </button>
                                        </div>
                                        <button class="init-delete-card" type="button" style="--max-ch: 7.3;">
											<?php
											echo esc_html__( 'Delete', 'unlimit' ); ?>
                                        </button>
                                    </div>
									<?php
								} ?>
                            </div>
                            <span class="ul-error ul-mt-5" id="ul-filing-id-error"
                                  style="display: none; width: 300px; position: relative">
                                    <?php
                                    echo esc_html__( 'Please fill out card number', 'unlimit' ); ?>
                                </span>
							<?php
						} ?>
                        <div id="unlimit-form"
							<?php
							if (
								$existing_filing_ids &&
								$is_recurring_enabled &&
								! $are_installments_enabled
							) { ?>
                                style="display: none;" <?php
							} ?> >
							<?php
							if ( ! $is_payment_page_required ) { ?>
                                <div class="ul-row-checkout">
                                    <div class="ul-col-md-12" style="position: relative;">
                                        <label for="ul-card-number" class="ul-label-form">
											<?php
											echo esc_html__( 'Card number', 'unlimit' ); ?>
                                            <em>*</em>
                                        </label>
                                        <div style="position: relative" class="ul-mt-5">
                                            <input type="text" onkeyup="formatUlCardField(this.id);"
                                                   onfocusout="validateUlCardFieldInput(this);"
                                                   class="ul-form-control"
                                                   autocomplete="off" minlength="16" maxlength="23"
                                                   name="unlimit_custom[cardNumber]" id="ul-card-number"
                                                   data-checkout="cardNumber"/>
                                            <span id="card-brand" class="card-brand"></span>
                                        </div>
                                        <span class="ul-error ul-mt-5" id="ul-card-number-error"
                                              data-main="#ul-card-number-error">
                                                <?php
                                                echo esc_html__( 'Card number is not valid', 'unlimit' ); ?>
                                            </span>
                                        <span class="ul-error ul-mt-5" id="ul-card-number-error-second"
                                              data-main="#ul-card-number-error">
                                                <?php
                                                echo esc_html__(
	                                                'Please fill out card number',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                    </div>
                                </div>


                                <div class="ul-row-checkout ul-pt-10" id="ul-card-holder-div">
                                    <div class="ul-col-md-12">
                                        <label for="ul-card-holder-name"
                                               class="ul-label-form">
											<?php
											echo esc_html__( 'Cardholder name', 'unlimit' ); ?>
                                            <em>*</em></label>
                                        <div style="position: relative" class="ul-mt-5">
                                            <input type="text" class="ul-form-control ul-mt-5" autocomplete="off"
                                                   minlength="2"
                                                   maxlength="50"
                                                   onkeyup="formatUlCardField(this.id);"
                                                   onfocusout="validateUlCardFieldInput(this);"
                                                   id="ul-card-holder-name" data-checkout="ul-card-holder-name"
                                                   name="unlimit_custom[cardholderName]"/>
                                        </div>

                                        <span class="ul-error ul-mt-5" id="ul-card-holder-name-error"
                                              data-main="#ul-card-holder-name-error">
                                                <?php
                                                echo esc_html__(
	                                                'Cardholder name is not valid',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                        <span class="ul-error ul-mt-5" id="ul-card-holder-name-error-second"
                                              data-main="#ul-card-holder-name-error">
                                                <?php
                                                echo esc_html__(
	                                                'Please fill out cardholder name',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                    </div>
                                </div>

                                <div class="ul-row-checkout ul-pt-10" style="align-items: end">
                                    <div class="ul-col-md-6 ul-pr-15">
                                        <label for="ul-card-expiration-date" class="ul-label-form">
											<?php
											echo esc_html__( 'Expiration date', 'unlimit' ); ?>
                                            <em>*</em>
                                        </label>
                                        <div style="position: relative" class="ul-mt-5">
                                            <input type="text" onkeyup="formatUlCardField(this.id);"
                                                   onfocusout="validateUlCardFieldInput(this);"
                                                   class="ul-form-control ul-mt-5" autocomplete="off"
                                                   placeholder="MM/YY"
                                                   maxlength="5" name="unlimit_custom[cardExpirationDate]"
                                                   id="ul-card-expiration-date" data-checkout="cardExpirationDate"/>
                                        </div>
                                        <span class="ul-error ul-mt-5" id="ul-card-expiration-date-error"
                                              data-main="#ul-card-expiration-date-error">
                                                <?php
                                                echo esc_html__( 'Invalid expiration date', 'unlimit' ); ?>
                                            </span>
                                        <span class="ul-error ul-mt-5" id="ul-card-expiration-date-error-second"
                                              data-main="#ul-card-expiration-date-error">
                                                <?php
                                                echo esc_html__(
	                                                'Please fill out an expiration date',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                    </div>

                                    <div class="ul-col-md-6">
                                        <label for="ul-cvc" class="ul-label-form">
											<?php
											echo esc_html__( 'CVV2/CVC2', 'unlimit' ); ?>
                                            <em>*</em>
                                        </label>
                                        <div style="position: relative" class="ul-mt-5">
                                            <input type="password" onkeyup="formatUlCardField(this.id);"
                                                   onfocusout="validateUlCardFieldInput(this);"
                                                   class="ul-form-control ul-mt-5" autocomplete="off" minlength="3"
                                                   maxlength="4" name="unlimit_custom[cvc]" id="ul-cvc"
                                                   data-checkout="cvc"/>
                                        </div>
                                        <span class="ul-error ul-mt-5" id="ul-cvc-error"
                                              data-main="#ul-cvc-error">
                                                <?php
                                                echo esc_html__(
	                                                'This CVV2/CVC2 is not valid',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                        <span class="ul-error ul-mt-5" id="ul-cvc-error-second"
                                              data-main="#ul-cvc-error">
                                                <?php
                                                echo esc_html__(
	                                                'Please fill out a CVV2/CVC2',
	                                                'unlimit'
                                                ); ?>
                                            </span>
                                    </div>
                                </div>
								<?php
							} ?>
							<?php
							if ( ! $are_installments_enabled && $is_recurring_enabled ) { ?>
                                <div class="ul-col-md-12 recurring-container" style="margin-bottom: -20px">
                                    <div class="recurring-frame">
                                        <div class="ul-row-checkout ul-pt-10">
                                            <div class="ul-row-checkout checkbox-container"
                                                 style="margin-top: 15px; flex-wrap: nowrap">
                                                <input type="checkbox" value="1" class="input-checkbox ul-recurring"
                                                       autocomplete="off" name="unlimit_custom[recurring]"
                                                       id="ul-recurring" data-checkout="recurring"/>
                                                <label for="ul-recurring" class="ul-label-form"
                                                       style="margin-top: -3px">
													<?php
													if ( $is_payment_page_required ) { ?>
														<?php
														echo esc_html__(
															'Save the card that will be entered next',
															'unlimit'
														); ?>
														<?php
													} ?>
													<?php
													if ( ! $is_payment_page_required ) { ?>
														<?php
														echo esc_html__(
															'Save this card for future use',
															'unlimit'
														); ?>
														<?php
													} ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
								<?php
							} ?>

							<?php
							if ( $are_installments_enabled ) { ?>
                                <div class="ul-row-checkout ul-pt-10">
                                    <div class="ul-col-md-12">
                                        <label for="ul-installments" class="ul-label-form">
											<?php
											echo esc_html__( 'Installments', 'unlimit' ); ?>
                                            <em>*</em></label>
                                        <div style="position: relative" class="ul-mt-5">
                                            <select class="ul-form-control ul-pointer ul-mt-5"
                                                    id="ul-installments"
                                                    onkeyup="formatUlCardField(this.id);"
                                                    onfocusout="validateUlCardFieldInput(this);"
                                                    onchange="
                                                            if (this.selectedIndex) validateUlCardFieldInput(this);
                                                            "
                                                    data-checkout="installments"
                                                    name="unlimit_custom[installments]">
												<?php
												if ( ! empty( $currency_symbol ) &&
												     isset( $installment_options['options'] )
												) {
													$first_option = esc_html__(
														'Select number of installments',
														'unlimit'
													);
													echo "<option value=''>$first_option</option>";

													foreach ( $installment_options['options'] as $option ) {
														if ( ! isset(
															$option['installments'], $option['amount']
														) ) {
															continue;
														}

														$installments = $option['installments'];
														$amount       = $option['amount'];
														echo "<option value='$installments'>" .
														     "$installments x $currency_symbol$amount" .
														     "</option>";
													}
												}
												?>
                                            </select>
                                        </div>
                                        <span class="ul-error ul-mt-5 ul-installments-error"
                                              id="ul-installments-error-second"
                                              data-main="#ul-installments-error">
                                                        <?php
                                                        echo esc_html__(
	                                                        'Please select number of installments',
	                                                        'unlimit'
                                                        ); ?>
                                                    </span>
                                    </div>
                                </div>
								<?php
							} ?>
							<?php
							if ( $is_cpf_required ) { ?>
								<?php
								if ( ! $is_payment_page_required ) { ?>
                                    <div class="ul-row-checkout ul-pt-10" style="margin-top: 20px; margin-bottom: 30px">
                                        <div class="ul-col-md-12" id="box-cpf" style="margin-top: -15px">
                                            <label for="ul-cpf" id="ul-cpf-label"
                                                   class="ul-label-form title-cpf">
												<?php
												echo esc_html__( 'CPF', 'unlimit' ); ?>
                                                <em>*</em></label>
                                            <div style="position: relative" class="ul-mt-5">
                                                <input type="text" onkeyup="formatUlCardField(this.id);"
                                                       onfocusout="validateUlCardFieldInput(this);"
                                                       class="ul-form-control" id="ul-cpf"
                                                       data-checkout="ul-cpf" name="unlimit_custom[cpf]"
                                                       autocomplete="off" maxlength="14"
                                                       placeholder="XXX.XXX.XXX-XX">
                                            </div>
                                            <span class="ul-error ul-mt-5" id="ul-cpf-error"
                                                  data-main="#ul-cpf-error">
                                                        <?php
                                                        echo esc_html__( 'CPF is invalid', 'unlimit' ); ?>
                                                    </span>
                                            <span class="ul-error ul-mt-5" id="ul-cpf-error-second"
                                                  data-main="#ul-cpf-error">
                                                        <?php
                                                        echo esc_html__(
	                                                        'Please fill out a CPF',
	                                                        'unlimit'
                                                        ); ?>
                                                    </span>
                                        </div>
                                    </div>
									<?php
								} ?>
								<?php
							} ?>
                        </div>
						<?php
						if ( $existing_filing_ids && $is_recurring_enabled && ! $are_installments_enabled ) { ?>
                            <div class="add_new_card" style="margin-top: 15px">
                                <button type="button" class="action add-card max-width" style="--max-ch: 24">
                                    <span><?php
	                                    echo esc_html__( 'Add new card', 'unlimit' ); ?></span>
                                </button>
                            </div>
							<?php
						} ?>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding: 10px 0 20px 0;margin: 20px 10px 0 10px;">
            <img src="<?php
			echo $cardBrandsLogoUrl ?>" width="120" height="35" alt="Credit card brands"/>
        </div>
    </div>
<?php
if ( $is_payment_page_required ) { ?>
    <script>
        unlimitIframePaymentMethods.push("creditcard");
    </script>
	<?php
}
