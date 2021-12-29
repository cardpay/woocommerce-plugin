<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-module.php';
require_once __DIR__ . '/log/class-wc-unlimint-logger.php';

class WC_Unlimint_Installments {

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

		$params = [
			'request_id'   => uniqid( '', true ),
			'total_amount' => $total_amount,
			'currency'     => get_woocommerce_currency()
		];

		$response = $this->sdk->get( '/installments/options_calculator', $params );

		$installment_options = [];
		if ( empty( $response ) ) {
			$this->logger->error( __METHOD__, 'Empty response, unable to get installment options' );
		} else if ( ! isset( $response['response']['options'] ) ) {
			$this->logger->error( __METHOD__, 'No options in response, unable to get installment options' );
		} else {
			$installment_options = [
				'currency' => get_woocommerce_currency_symbol(),
				'options'  => $this->build_installment_options( $response, $total_amount )
			];
		}

		return $installment_options;
	}

	private function build_installment_options( $response, $total_amount ) {
		$options_response = $response['response']['options'];

		$options = [];
		foreach ( $options_response as $option ) {
			if ( ! isset( $option['installments'], $option['amount'] ) ) {
				continue;
			}

			$installments = $option['installments'];
			$amount       = $option['amount'];

			$options[] = [
				'installments' => $installments,
				'amount'       => $this->format_amount( $amount )
			];
		}

		return array_merge(
			[
				[
					'installments' => 1,
					'amount'       => $this->format_amount( $total_amount )
				]
			],
			$options
		);
	}

	private function format_amount( $amount ) {
		if ( empty( $amount ) ) {
			return $amount;
		}

		return number_format( $amount, 2 );
	}
}