<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-module.php';
require_once __DIR__ . '/log/class-wc-unlimint-logger.php';

class WC_Unlimint_Installments {
	private const INSTALLMENTS_MIN = 1;

	/**
	 * @var Unlimint_Sdk
	 */
	private $sdk;

	/**
	 * @var WC_Unlimint_Logger|null
	 */
	private $logger;

	public function __construct() {
		$this->sdk    = WC_Unlimint_Module::get_unlimint_sdk( WC_Unlimint_Custom_Gateway::GATEWAY_ID );
		$this->logger = new WC_Unlimint_Logger();
	}

	public function get_installment_options() {
		// avoid several API calls
		global $wp;
		$order_id_to_pay = $wp->query_vars['order-pay'];              // 'Pay for order' WC page
		if ( 1 !== (int) doing_filter( 'wc_ajax_update_order_review' ) && empty( $order_id_to_pay ) ) {
			return [];
		}

		if ( ! empty( $order_id_to_pay ) ) {
			$order        = wc_get_order( $order_id_to_pay );
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
		if ( get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) === 'no' ) {
			return [];
		}

		$options                        = [];
		$getMaximumAcceptedInstallments = (int) get_option(
			WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			WC_Unlimint_Admin_BankCard_Fields::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS );


		if ( get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_TYPE ) === 'IF' ) {
			$maximumAcceptedInstallmentsForIf = [ 1, 3, 6, 9, 12, 18 ];
			for ( $installments = self::INSTALLMENTS_MIN; $installments <= $getMaximumAcceptedInstallments; $installments ++ ) {
				if ( in_array( $installments, $maximumAcceptedInstallmentsForIf ) ) {
					$options[] = [
						'installments' => $installments,
						'amount'       => $this->format_amount( $total_amount / $installments )
					];
				}
			}

			return $options;
		}


		for ( $installments = self::INSTALLMENTS_MIN; $installments <= $getMaximumAcceptedInstallments; $installments ++ ) {
			$options[] = [
				'installments' => $installments,
				'amount'       => $this->format_amount( $total_amount / $installments )
			];
		}

		return $options;
	}

	private function format_amount( $amount ) {
		if ( empty( $amount ) ) {
			return $amount;
		}

		return number_format( $amount, 2 );
	}
}