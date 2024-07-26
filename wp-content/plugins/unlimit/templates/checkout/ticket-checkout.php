<?php

defined( 'ABSPATH' ) || exit;

$boletoLogoUrl = plugins_url( '../../assets/images/boleto.png', __FILE__ );
?>

<div class='ul-panel-custom-checkout' title="Unlimit Payment Page">
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimit-form-ticket'>
                    <div id='form-ticket'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8' id='box-cpf'>
                                <label for='ul-cpf-ticket' id='ul-cpf-label'
                                       class='ul-label-form title-cpf'><?php
									echo esc_html__( 'CPF', 'unlimit' ); ?>
                                    <em>*</em></label>
                                <div style="position: relative" class="ul-mt-5">
                                <input type='text'
                                       onkeyup="formatUlBoletoCpf(this.id);" onfocusout="validateUlBoletoInput();"
                                       class='ul-form-control'
                                       id='ul-cpf-ticket' data-checkout='ul-cpf-ticket' name='unlimit_ticket[cpf]'
                                       autocomplete='off' maxlength='14'
                                       placeholder='XXX.XXX.XXX-XX'>
                                </div>
                                <span class="ul-error ul-mt-5" id="ul-cpf-ticket-error"
                                      data-main="#ul-cpf-error">
                                    <?php
                                    echo esc_html__( 'CPF is invalid', 'unlimit' ); ?>
                                </span>
                                <span class="ul-error ul-mt-5" id="ul-cpf-ticket-error-second"
                                      data-main="#ul-cpf-error">
                                    <?php
                                    echo esc_html__( 'Please fill out a CPF', 'unlimit' ); ?>
                                </span>
                                <img src='<?php
								echo $boletoLogoUrl ?>' width='53' height='35' alt='Boleto' style="margin-top: 30px"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type='text/javascript'>
    function validateUlBoletoInput() {
        setTimeout(validateUlBoletoCpf(), 1);
    }
</script>