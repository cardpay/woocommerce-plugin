<?php

defined( 'ABSPATH' ) || exit;

$boletoLogoUrl = plugins_url( '../../assets/images/boleto.png', __FILE__ );
?>

<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimint-form-ticket'>
                    <div id='form-ticket'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8' id='box-cpf'>
                                <label for='ul-cpf-ticket' id='ul-cpf-label'
                                       class='ul-label-form title-cpf'><?php echo esc_html__( 'CPF', 'unlimint' ); ?> <em>*</em></label>
                                <input type='text'
                                       class='ul-form-control'
                                       id='ul-cpf-ticket' data-checkout='ul-cpf-ticket' name='unlimint_ticket[cpf]'
                                       onkeyup='handleUlBoletoInput();' autocomplete='off' maxlength='14'
                                       placeholder='XXX.XXX.XXX-XX'><br/>
                                <span class='ul-error' data-main='#ul-cpf-ticket'
                                      id='boleto-cpf-error'><?php echo esc_html__( 'Invalid CPF', 'unlimint' ); ?></span><br/>
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
    function handleUlBoletoInput() {
        setTimeout('handleUlBoletoCpf()', 1);
    }
</script>