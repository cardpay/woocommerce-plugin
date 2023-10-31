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

		$is_cvc_set = isset(
			              $_POST['unlimit_custom'] ) &&
		              ! empty( $_POST['unlimit_custom']['cvc']
		              );
		if (
			$is_cvc_set &&
			( mb_strlen(
				  $_POST['unlimit_custom']['cvc'] ) < 3 ||
			  mb_strlen( $_POST['unlimit_custom']['cvc'] ) > 4
			)
		) {
			wc_add_notice( __( 'CVC length must be 3 or 4' ), 'error' );
		}

		$amount                      = WC()->cart->get_totals()['total'];
		$getMinimalInstallmentAmount = get_option(
			WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			WC_Unlimit_Admin_BankCard_Fields::FIELD_MINIMUM_INSTALLMENT_AMOUNT
		);
		if ( $_POST['unlimit_custom']['installments'] > 1 &&
		     $getMinimalInstallmentAmount > $amount / $_POST['unlimit_custom']['installments']
		) {
			wc_add_notice( '<p>' .
			               __( "Minimum amount of the order with installments must be greater than",
				               "unlimit" ) .
			               ' ' . $amount . '</p>',
				'error' );
		}
	}
}