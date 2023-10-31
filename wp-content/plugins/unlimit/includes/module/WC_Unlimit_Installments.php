<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Module.php';
require_once __DIR__ . '/log/WC_Unlimit_Logger.php';

class WC_Unlimit_Installments {

	/**
	 * @var WC_Unlimit_Sdk
	 */
	private $sdk;

	/**
	 * @var WC_Unlimit_Logger|null
	 */
	private $logger;

	public function __construct() {
		$this->sdk    = WC_Unlimit_Module::get_unlimit_sdk( WC_Unlimit_Custom_Gateway::GATEWAY_ID );
		$this->logger = new WC_Unlimit_Logger();
	}

	public function get_installment_options() {
		if ( ! WC()->session ) {
			return [];
		}

		$order = WC()->session->get( 'order_awaiting_payment' );

		if ( is_a( $order, 'WC_Order' ) ) {
			$total_amount = $order->get_total();
		} else {
			$cart = WC()->cart;
			if ( is_null( $cart ) ) {
				return [];
			}

			$total_amount = $cart->get_total( 'raw' );
		}

		return [
			'currency' => get_woocommerce_currency_symbol(),
			'options'  => $this->build_installment_options( $total_amount )
		];
	}

	private function build_installment_options( $total_amount ) {
		if ( get_option(
			     WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			     WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) === 'no'
		) {
			return [];
		}

		$options = [];

		$minimum_installment_amount = (float) get_option(
			WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			WC_Unlimit_Admin_BankCard_Fields::FIELD_MINIMUM_INSTALLMENT_AMOUNT
		);

		$installments_range = $this->get_installments_range(
			get_option(
				WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS
			)
		);

		foreach ( $installments_range as $installments ) {
			$amount = $total_amount / $installments;
			if (
				( $amount < $minimum_installment_amount ) &&
				( $installments > 1 ) &&
				( $minimum_installment_amount > 0 ) ) {
				break;
			}
			$options[] = [
				'installments' => $installments,
				'amount'       => $this->format_amount( $total_amount / $installments )
			];
		}

		return $options;
	}

	private function get_allowed_installment_range() {
		if ( get_option(
			     WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			     WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_TYPE
		     ) === 'MF_HOLD' ) {
			return [ 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 ];
		} else {
			return [ 3, 6, 9, 12, 18 ];
		}
	}

	private function append_installment_option( &$result, $value, $range ) {
		if ( in_array( $value, $range ) ) {
			$result[] = $value;
		}
	}

	private function normalize_installment_array( $array ) {
		$array[] = 1;
		$result  = array_unique( $array );
		sort( $result );

		return ( empty( $result ) ) ? [ 1 ] : $result;
	}

	private function get_installments_range( $settings ) {
		$result = [];

		$range = $this->get_allowed_installment_range();

		foreach ( explode( ',', trim( $settings ) ) as $value ) {
			if ( strpos( $value, '-' ) !== false ) {
				$value = explode( '-', $value );
				if ( count( $value ) !== 2 ) {
					continue;
				}
				for ( $i = (int) $value[0]; $i <= ( (int) $value[1] ); $i ++ ) {
					$this->append_installment_option( $result, $i, $range );
				}
			} else {
				$this->append_installment_option( $result, (int) $value, $range );
			}
		}

		return $this->normalize_installment_array( $result );
	}

	private function format_amount( $amount ) {
		if ( empty( $amount ) ) {
			return $amount;
		}

		return number_format( $amount, 2 );
	}
}
