<?php

defined( 'ABSPATH' ) || exit;

$pixLogoUrl = plugins_url( '../../assets/images/pix.png', __FILE__ );
?>

<div class='ul-panel-custom-checkout'>
    <div class='ul-row-checkout'>
        <div class='ul-col-md-12'>
            <div class='frame-tarjetas'>
                <div id='unlimit-form-pix'>
                    <div id='form-pix'>
                        <div class='ul-row-checkout'>
                            <div class='ul-col-md-8' id='box-cpf'>
                                <label for='ul-cpf-pix' id='ul-cpf-label'
                                       class='ul-label-form title-cpf'><?php echo esc_html__( 'CPF', 'unlimit' ); ?>
                                    <em>*</em></label>
                                <input type='text' class='ul-form-control'
                                       onkeyup="formatUlBoletoCpf(this.id);" onfocusout="validateUlPixInput();"
                                       id='ul-cpf-pix' data-checkout='ul-cpf-pix' name='unlimit_pix[cpf]'
                                       autocomplete='off' maxlength='14'
                                       placeholder='XXX.XXX.XXX-XX'><br/>
                                <span class="ul-error ul-mt-5" id="ul-cpf-pix-error"
                                      data-main="#ul-cpf-error">
                                    <?php echo esc_html__( 'CPF is invalid', 'unlimit' ); ?>
                                </span>
                                <span class="ul-error ul-mt-5" id="ul-cpf-pix-error-second"
                                      data-main="#ul-cpf-error">
                                    <?php echo esc_html__( 'Please fill out a CPF', 'unlimit' ); ?>
                                </span><br>
                                <img src='<?php echo $pixLogoUrl ?>' width='99' height='35' alt='Pix'/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type='text/javascript'>
    function validateUlPixInput() {
        setTimeout(validateUlPixCpf(), 1);
    }
</script>
