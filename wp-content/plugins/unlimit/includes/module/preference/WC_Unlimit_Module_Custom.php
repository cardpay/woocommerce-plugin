<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Module_Custom extends WC_Unlimit_Module_Abstract {
	public const INSTALLMENTS = 'installments';

	public $request_type = WC_Unlimit_Constants::PAYMENT_DATA;

	/**
	 * @param WC_Unlimit_Gateway_Abstract $payment
	 * @param WC_Order $order
	 * @param array $post_fields
	 * @param bool $post_can_be_empty
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields, $post_can_be_empty ) {
		parent::__construct( $payment, $order, $post_fields );

		if ( ! isset( $this->post_fields['unlimit_custom'] ) && ! $post_can_be_empty ) {
			$error_message = 'POST bank card fields are not set';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimit_Exception( $error_message );
		}

		$this->build_api_request();
	}

	private function build_api_request() {
		global $wpdb;

		$api_request       = $this->get_common_api_request();
		$field_name_prefix = WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX;

		$installment_type         = $this->get_installment_type( $field_name_prefix );
		$are_installments_enabled = $this->are_installments_enabled( $field_name_prefix );

		$is_preauth = $this->is_preauth( $field_name_prefix, $api_request );
		$customer   = WC()->customer;

		if ( isset( $this->post_fields['unlimit_custom'] ) ) {
			$card_post_fields = $this->post_fields['unlimit_custom'];
			$installments     = $this->get_installments( $card_post_fields );

			if ( $are_installments_enabled ) {
				$this->add_installment_data( $api_request, $installment_type, $installments, $is_preauth );
			} elseif ( ! empty( $card_post_fields['recurring'] ) ) {
				$this->prepare_recurring_data( $api_request, true );
			} elseif ( ! empty( $card_post_fields['filing_id'] ) ) {
				$table_name = $wpdb->prefix . 'ul_recurring_data';
				$filing_id  = $wpdb->get_var( $wpdb->prepare(
					"SELECT filing_id FROM $table_name WHERE recurring_data_id = %d",
					$card_post_fields['filing_id']
				) );

				if ( $filing_id ) {
					$this->prepare_recurring_data( $api_request, false, $filing_id );
				}
			}

			$this->add_customer_data( $api_request, $field_name_prefix, $card_post_fields );
			$this->add_card_account_data( $api_request, $field_name_prefix, $card_post_fields, $customer );
		}

		$this->finalize_api_request( $api_request, $field_name_prefix );
	}

	private function prepare_recurring_data( &$api_request, $begin, $filing_id = null ) {
		$this->request_type                 = WC_Unlimit_Constants::RECURRING_DATA;
		$api_request[ $this->request_type ] = $api_request[ WC_Unlimit_Constants::PAYMENT_DATA ];
		unset( $api_request[ WC_Unlimit_Constants::PAYMENT_DATA ] );

		$api_request[ $this->request_type ]['initiator'] = 'cit';
		$api_request[ $this->request_type ]['begin']     = $begin;

		if ( $filing_id !== null ) {
			$api_request[ $this->request_type ]['filing'] = [ 'id' => $filing_id ];
		}
	}

	private function get_installment_type( $field_name_prefix ) {
		return get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_TYPE );
	}

	private function are_installments_enabled( $field_name_prefix ) {
		return 'yes' === get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED );
	}

	private function is_preauth( $field_name_prefix, &$api_request ) {
		$is_preauth =
			'no' === get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_CAPTURE_PAYMENT );
		if ( $is_preauth ) {
			$api_request[ $this->request_type ]['preauth'] = true;
			WC_Unlimit_Helper::set_order_meta( $this->order,
				WC_Unlimit_Constants::ORDER_META_PREAUTH_FIELDNAME,
				'true' );
		}

		return $is_preauth;
	}

	private function get_installments( $card_post_fields ) {
		return isset( $card_post_fields[ self::INSTALLMENTS ] ) ? (int) $card_post_fields[ self::INSTALLMENTS ] : 0;
	}

	private function add_installment_data( &$api_request, $installment_type, $installments, $is_preauth ) {
		if ( $installments > 0 ) {
			$api_request[ $this->request_type ]['installment_type']   = $installment_type;
			$api_request[ $this->request_type ][ self::INSTALLMENTS ] = $installments;

			if ( $installment_type === 'IF' && $installments > 1 && $is_preauth ) {
				unset( $api_request[ $this->request_type ]['preauth'] );
			}
		}
	}

	private function add_customer_data( &$api_request, $field_name_prefix, $card_post_fields ) {
		$is_cpf_required = 'yes' === get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_ASK_CPF );
		if ( $is_cpf_required && ! empty( $card_post_fields['cpf'] ) ) {
			$api_request['customer']['identity'] = $card_post_fields['cpf'];
		}
	}

	private function add_card_account_data( &$api_request, $field_name_prefix, $card_post_fields, $customer ) {
		$is_api_access_mode =
			'gateway' === get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_API_ACCESS_MODE );
		if ( $is_api_access_mode ) {
			$api_request['card_account'] = [
				'card'            => [
					'pan'           => str_replace( ' ', '', $card_post_fields['cardNumber'] ),
					'holder'        => $card_post_fields['cardholderName'],
					'expiration'    => substr_replace( $card_post_fields['cardExpirationDate'], '20', 3, 0 ),
					'security_code' => $card_post_fields['cvc'],
				],
				'billing_address' => [
					'country'     => $customer->get_billing_country(),
					'state'       => $customer->get_billing_state(),
					'zip'         => $customer->get_billing_postcode(),
					'city'        => $customer->get_billing_city(),
					'phone'       => preg_replace( '/[^\D]/', '', $customer->get_billing_phone() ),
					'addr_line_1' => $customer->get_billing_address_1(),
					'addr_line_2' => $customer->get_billing_address_2(),
				],
			];
		}
	}

	private function finalize_api_request( &$api_request, $field_name_prefix ) {
		$api_request['payment_method']                  = 'BANKCARD';
		$api_request[ $this->request_type ]['amount']   = $this->order->get_total();
		$api_request[ $this->request_type ]['currency'] = get_woocommerce_currency();

		$dynamic_descriptor = get_option( $field_name_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_DYNAMIC_DESCRIPTOR );
		if ( ! empty( $dynamic_descriptor ) ) {
			$api_request[ $this->request_type ]['dynamic_descriptor'] = $dynamic_descriptor;
		}

		$this->api_request = $api_request;
	}
}
