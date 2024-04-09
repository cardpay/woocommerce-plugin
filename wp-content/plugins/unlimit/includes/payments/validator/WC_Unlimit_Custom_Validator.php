<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_General_Validator.php';

class WC_Unlimit_Custom_Validator extends WC_Unlimit_General_Validator {

	private const VALIDATION_RULES = [
		'postcode' => [ 'Postcode / ZIP', 12 ],
	];

	public function validate() {
		$this->set_validation_rules( self::VALIDATION_RULES );
		parent::validate();

		$unlimit_custom = isset( $_POST['unlimit_custom'] ) ? $_POST['unlimit_custom'] : null;

		if ( ! is_array( $unlimit_custom ) ) {
			return;
		}

		$cvc = isset( $unlimit_custom['cvc'] ) ? $unlimit_custom['cvc'] : null;

		if ( ! empty( $cvc ) && ( mb_strlen( $cvc ) < 3 || mb_strlen( $cvc ) > 4 ) ) {
			wc_add_notice( __( 'CVC length must be 3 or 4' ), 'error' );
		}

		$amount = WC()->cart->get_totals()['total'];
		$min_installment_amount = get_option(
			WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			WC_Unlimit_Admin_BankCard_Fields::FIELD_MINIMUM_INSTALLMENT_AMOUNT
		);

		$installments = isset( $unlimit_custom['installments'] ) ? (int) $unlimit_custom['installments'] : 0;

		if ( $installments > 1 && $min_installment_amount > 0 && $min_installment_amount > $amount / $installments ) {
			wc_add_notice( '<p>' . __( "Minimum amount of the order with installments must be greater than",
					"unlimit" ) . ' ' . $amount . '</p>',
				'error' );
		}
	}
}
