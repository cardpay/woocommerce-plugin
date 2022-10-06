<?php

defined( 'ABSPATH' ) || exit;

$boletoLogoUrl = plugins_url( '../../assets/images/boleto.png', __FILE__ );
?>

<div class='ul-panel-custom-checkout' title="Unlimint Payment Page">
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimint-form-ticket'>
                    <div id='form-ticket'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8' id='box-cpf'>
                                <label for='ul-cpf-ticket' id='ul-cpf-label'
                                       class='ul-label-form title-cpf'><?php echo esc_html__( 'CPF', 'unlimint' ); ?>
                                    <em>*</em></label>
                                <input type='text'
                                       onkeyup="formatUlBoletoCpf(this.id);" onfocusout="validateUlBoletoInput();"
                                       class='ul-form-control'
                                       id='ul-cpf-ticket' data-checkout='ul-cpf-ticket' name='unlimint_ticket[cpf]'
                                       autocomplete='off' maxlength='14'
                                       placeholder='XXX.XXX.XXX-XX'><br/>
                                <span class="ul-error ul-mt-5" id="ul-cpf-ticket-error"
                                      data-main="#ul-cpf-error"><?php echo esc_html__( 'CPF is invalid', 'unlimint' ); ?></span>
                                <span class="ul-error ul-mt-5" id="ul-cpf-ticket-error-second"
                                      data-main="#ul-cpf-error"><?php echo esc_html__( 'Please fill out a CPF', 'unlimint' ); ?></span><br>
                                <img src='<?php echo $boletoLogoUrl ?>' width='53' height='35' alt='Boleto'/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type='text/javascript'>
    function getBillingZipErrorMessage() {
        return '<?php echo strip_tags(__( '<strong>Billing: ZIP / Postal code</strong> must be 8 characters.', 'unlimint' )); ?>';
    }
    function getShippingZipErrorMessage() {
        return '<?php echo strip_tags(__( '<strong>Shipping: ZIP / Postal code</strong> must be 8 characters.', 'unlimint' )); ?>';
    }
    function validateUlBoletoInput() {
        setTimeout(validateUlBoletoCpf(), 1);
    }
</script>