<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-general-validator.php';

class WC_Unlimint_Custom_Validator extends WC_Unlimint_General_Validator {
	public function validate() {
		parent::validate();

		$is_cvc_set = isset( $_POST['unlimint_custom'] ) && ! empty( $_POST['unlimint_custom']['cvc'] );
		if ( $is_cvc_set && ( mb_strlen( $_POST['unlimint_custom']['cvc'] ) < 3 || mb_strlen( $_POST['unlimint_custom']['cvc'] ) > 4 ) ) {
			wc_add_notice( __( 'CVC length must be 3 or 4' ), 'error' );
		}
	}
}