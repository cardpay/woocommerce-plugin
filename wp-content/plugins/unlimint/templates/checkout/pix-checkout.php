<?php

defined( 'ABSPATH' ) || exit;

$pixLogoUrl = plugins_url( '../../assets/images/pix.png', __FILE__ );
?>

<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimint-form-pix'>
                    <div id='form-pix'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8' id='box-cpf'>
                                <label for='ul-cpf-pix' id='ul-cpf-label'
                                       class='ul-label-form title-cpf'><?php echo esc_html__( 'CPF', 'unlimint' ); ?> <em>*</em></label>
                                <input type='text' class='ul-form-control'
                                       id='ul-cpf-pix' data-checkout='ul-cpf-pix' name='unlimint_pix[cpf]'
                                       onkeyup='handleUlPixInput();' autocomplete='off' maxlength='14'
                                       placeholder='XXX.XXX.XXX-XX'><br/>
                                <span class='ul-error' data-main='#ul-cpf-pix'
                                      id='pix-cpf-error'><?php echo esc_html__( 'Invalid CPF', 'unlimint' ); ?></span><br/>
                                <img src='<?php echo $pixLogoUrl ?>' width='99' height='35' alt='Pix' />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type='text/javascript'>
    function handleUlPixInput() {
        setTimeout('handleUlPixCpf()', 1);
    }
</script>