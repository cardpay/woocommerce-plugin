<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Module_Custom extends WC_Unlimint_Module_Abstract {

	/**
	 * @param WC_Unlimint_Gateway_Abstract $payment Payment.
	 * @param WC_Order $order
	 * @param array|null $post_fields Custom checkout.
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields ) {
		parent::__construct( $payment, $order, $post_fields );

		if ( ! isset( $this->post_fields['unlimint_custom'] ) ) {
			$error_message = 'POST bank card fields are not set';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimint_Exception( $error_message );
		}

		$this->build_api_request();
	}

	private function build_api_request() {
		$card_post_fields = $this->post_fields['unlimint_custom'];
		$api_request      = $this->get_common_api_request();

		$fieldname_prefix = WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX;
		$api_section      = 'payment_data';

		$are_installments_enabled = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) );
		if ( $are_installments_enabled && isset( $card_post_fields['installments'] ) ) {
			$installments = (int) $card_post_fields['installments'];
			if ( $installments > 1 ) {
				$api_section = 'recurring_data';

				$api_request[ $api_section ] = [
					'installment_type' => 'MF_HOLD',
					'initiator'        => 'cit',
					'interval'         => '30',
					'period'           => 'day',
					'payments'         => $installments,
					'trans_type'       => '01'
				];
			}
		}

		$api_request['payment_method']           = 'BANKCARD';
		$api_request[ $api_section ]['amount']   = $this->order->get_total();
		$api_request[ $api_section ]['currency'] = get_woocommerce_currency();

		$dynamic_descriptor = get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_DYNAMIC_DESCRIPTOR );
		if ( ! empty( $dynamic_descriptor ) ) {
			$api_request[ $api_section ]['dynamic_descriptor'] = $dynamic_descriptor;
		}

		$is_preauth = ( 'no' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_CAPTURE_PAYMENT ) );
		if ( $is_preauth ) {
			$api_request[ $api_section ]['preauth'] = true;
			WC_Unlimint_Helper::set_order_meta( $this->order, WC_Unlimint_Constants::ORDER_META_PREAUTH_FIELDNAME, 'true' );
		}

		$is_cpf_required = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_ASK_CPF ) );
		if ( $is_cpf_required && ! empty( $card_post_fields['cpf'] ) ) {
			$api_request['customer']['identity'] = $card_post_fields['cpf'];
		}

		$customer                    = WC()->customer;
		$api_request['card_account'] = [
			'card'            => [
				'pan'           => str_replace( ' ', '', $card_post_fields['cardNumber'] ),
				'holder'        => $card_post_fields['cardholderName'],
				'expiration'    => $card_post_fields['cardExpirationDate'],
				'security_code' => $card_post_fields['cvc']
			],
			'billing_address' => [
				'country'     => $customer->get_billing_country(),
				'state'       => $customer->get_billing_state(),
				'zip'         => $customer->get_billing_postcode(),
				'city'        => $customer->get_billing_city(),
				'phone'       => $customer->get_billing_phone(),
				'addr_line_1' => $customer->get_billing_address_1()
			]
		];

		$this->api_request = $api_request;
	}
}
