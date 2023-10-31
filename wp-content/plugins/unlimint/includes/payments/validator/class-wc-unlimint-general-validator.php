<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_General_Validator {
	const STRONG_TAG = '<strong>';
	const STR = 'Postcode / ZIP';

	private const BILLING = 'billing';
	private const SHIPPING = 'shipping';

	private const VALIDATION_RULES = [
		'address_1' => [ 'Street / Address', 50 ],
		'address_2' => [ 'Street 2 / Address 2', 50 ],
		'city'      => [ 'Town / City', 50 ],
		'state'     => [ 'State / Country', 50 ],
	];

	/**
	 * @var array
	 */
	private $validationRules = [];

	public function validate() {
		$this->validate_address( self::BILLING );
		$this->validate_billing_and_shipping_phone();
		$this->validate_address( self::SHIPPING );
	}

	public function validate_billing_and_shipping_phone() {
		$fields = [ 'billing_phone' => 'Billing' ];

		if ( ! empty( $_POST['shipping_phone'] ) ) {
			$fields['shipping_phone'] = 'Shipping';
		}

		foreach ( $fields as $field => $address ) {
			if ( ! empty( $_POST[ $field ] ) ) {
				$cleanedPhone    = $this->clean_phone( $_POST[ $field ] );
				$_POST[ $field ] = $cleanedPhone;
				if ( strlen( $cleanedPhone ) < 8 || strlen( $cleanedPhone ) > 18 ) {
					wc_add_notice(
						__( self::STRONG_TAG . $address . ': Phone</strong>, valid value is from 8 to 18 characters.',
							'unlimint' ),
						'error'
					);

					return false;
				}
			}
		}

		return $cleanedPhone;
	}

	private function clean_phone( $phone ) {
		return preg_replace( "/[^\d]+/", "", $phone );
	}

	private function validate_address( $addressType ) {
		if (
			self::SHIPPING === $addressType &&
			( empty( $_POST['ship_to_different_address'] ) || $_POST['ship_to_different_address'] !== '1' )
		) {
			return;
		}

		$completeRules = array_merge( self::VALIDATION_RULES, $this->validationRules );
		$paymentMethod = $_POST['payment_method'];

		foreach ( $completeRules as $post_key => $error_data_value ) {
			$errorMessage = $error_data_value[0];
			$maxLength    = $error_data_value[1];

			$addressField = $addressType . '_' . $post_key;
			$fieldValue   = $_POST[ $addressField ];

			$this->validate_zip( $paymentMethod, $errorMessage, $maxLength, $addressType, $addressField, $fieldValue );

			if ( empty( $fieldValue ) ) {
				continue;
			}

			if ( $errorMessage !== self::STR && mb_strlen( $fieldValue ) > $maxLength ) {
				wc_add_notice(
					__( '' . self::STRONG_TAG . ucfirst( $addressType ) . ": $errorMessage</strong> must be $maxLength characters.",
						'unlimint' ), 'error' );
			}
		}
	}

	/**
	 * @param array $validationRules
	 */
	public function set_validation_rules( array $validationRules ) {
		$this->validationRules = $validationRules;
	}

	/**
	 * @param mixed $paymentMethod
	 * @param mixed $errorMessage
	 * @param mixed $maxLength
	 * @param $addressType
	 */
	private function validate_zip( $paymentMethod, $errorMessage, $maxLength, $addressType, $addressField, $fieldValue ): void {
		if ( $paymentMethod === 'woo-unlimint-ticket' && $errorMessage === self::STR ) {
			if ( strlen( $fieldValue ) > 17 ||
			     strlen( $this->clean_phone( $fieldValue ) ) !== $maxLength ) {
				wc_add_notice(
					__( '' . self::STRONG_TAG . ucfirst( $addressField ) . ": $errorMessage</strong> must be $maxLength characters.",
						'unlimint' ),
					'error'
				);
			} else {
				$_POST[ $addressType . '_postcode' ] = $this->clean_phone( $fieldValue );
			}
		}

		if ( ( $paymentMethod === 'woo-unlimint-custom' || $paymentMethod === 'woo-unlimint-pix' ) &&
		     $errorMessage === self::STR && strlen( $fieldValue ) > $maxLength ) {
			wc_add_notice(
				__( "<strong>" . ucfirst( $fieldValue ) .
				    ": $errorMessage</strong> must be $maxLength characters.", 'unlimint' ),
				'error'
			);
		}
	}
}