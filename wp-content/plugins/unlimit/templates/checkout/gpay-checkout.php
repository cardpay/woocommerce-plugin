<?php

defined( 'ABSPATH' ) || exit;

$gpayBrandsLogoUrl = plugins_url( '../../assets/images/gpay.png', __FILE__ );
?>

<div class="ul-panel-custom-checkout">
    <div class="ul-row-checkout" id="co-cardpay-form-gpay" style="display: none">
        <div class="ul-col-md-12">
            <div class="frame-tarjetas">
                <div id="unlimit-form" style="display: none">
                    <div class="ul-row-checkout">
                        <div class="mp-box-inputs mp-col-100" id="buttonContainer">
                            <input id="container" name="cardpay_custom_gpay[signature]"
                                   value="<?php
							       echo $google_merchant_id ?>" style="display: none"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="padding: 10px 0 20px 0; margin-bottom: 35px">
        <img src="<?php
		echo $gpayBrandsLogoUrl ?>" width="99" height="35" alt="Google Pay brands"/>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        onGooglePayLoaded();
    });
</script>