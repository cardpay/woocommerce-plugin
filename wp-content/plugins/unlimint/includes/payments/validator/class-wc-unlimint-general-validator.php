<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_General_Validator {
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
	private $validation_rules = [];

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
						__( '<strong>' . $address . ': Phone</strong>, valid value is from 8 to 18 characters.',
							'unlimit' ),
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

	private function validate_address( $address_type ) {
		if ( self::SHIPPING === $address_type && ( empty( $_POST['ship_to_different_address'] ) || $_POST['ship_to_different_address'] !== '1' ) ) {
			return;
		}

		$complete_rules = array_merge( self::VALIDATION_RULES, $this->validation_rules );
		foreach ( $complete_rules as $post_key => $error_data_value ) {
			$error_message = $error_data_value[0];
			$max_length    = $error_data_value[1];

			$address_field = $address_type . '_' . $post_key;
			if ( empty( $_POST[ $address_field ] ) ) {
				continue;
			}

			if ( mb_strlen( $_POST[ $address_field ] ) > $max_length ) {
				if ( $post_key === 'postcode' ) {
					wc_add_notice( __( "<strong>" . ucfirst( $address_type ) . ": $error_message</strong> must be $max_length characters.",
						'unlimint' ), 'error' );
				} else {
					wc_add_notice( __( "<strong>" . ucfirst( $address_type ) . ": $error_message</strong>, valid value must be $max_length characters.",
						'unlimint' ), 'error' );
				}
			}
		}
	}

	/**
	 * @param array $validation_rules
	 */
	public function set_validation_rules( array $validation_rules ) {
		$this->validation_rules = $validation_rules;
	}
}