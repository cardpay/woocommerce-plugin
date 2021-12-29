<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';

class WC_Unlimint_Refund {
	const ERROR_BOLETO = 'Refund is not available for Boleto';
	const ERROR_INSTALLMENT = 'Refund is not available for installment payment';
	const ERROR_CARD = "Refund is available for credit card payment only";

	/**
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	/**
	 * @var Unlimint_Sdk
	 */
	public $unlimint_sdk;

	public function __construct( $gateway_id ) {
		$this->logger       = new WC_Unlimint_Logger();
		$this->unlimint_sdk = new Unlimint_Sdk( $gateway_id );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$this->logger->info( __FUNCTION__, 'Refund processing has started' );

		try {
			$this->validate_order_for_refund( $amount, $order_id );
		} catch ( WC_Unlimint_Exception $e ) {
			$error_message = $e->getMessage();
			$this->logger->error( __FUNCTION__, $error_message );

			return new WP_Error( 'wc_ul_refund_failed', "Refund for order #$order_id has failed with validation error: " . __( $error_message ) );
		}

		$request     = $this->get_refund_request( $order_id, $amount, $reason );
		$refund_info = $this->unlimint_sdk->post( '/refunds', wp_json_encode( $request ) );

		if ( isset( $refund_info['status'] ) && (int) $refund_info['status'] === 201 ) {
			$this->logger->info( __FUNCTION__, "Successful refund for order #$order_id: " . wp_json_encode( $refund_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

			return true;
		}

		$error_message = '';
		if ( isset( $refund_info['response']['message'] ) ) {
			$error_message = $refund_info['response']['message'];
			$this->logger->error( __FUNCTION__, "Refund for order #$order_id has failed with error: " . $error_message );
		}

		return new WP_Error( 'wc_ul_api_refund_failed', "Refund for order #$order_id has failed with API error: $error_message" );
	}

	private function validate_order_for_refund( $amount, $order_id ) {
		if ( is_null( $amount ) || (float) $amount <= 0 ) {
			throw new WC_Unlimint_Exception( "Invalid refund amount for order #$order_id");
		}

		$order              = wc_get_order( $order_id );
		$gateway            = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME );
		$payment_type_field = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_PAYMENT_TYPE_FIELDNAME );

		if ( WC_Unlimint_Constants::BOLETO_GATEWAY === $gateway ) {
			throw new WC_Unlimint_Exception( __( self::ERROR_BOLETO ) );
		}

		if ( WC_Unlimint_Constants::BANKCARD_GATEWAY === $gateway && WC_Unlimint_Constants::PAYMENT_TYPE_RECURRING === $payment_type_field ) {
			throw new WC_Unlimint_Exception( __( self::ERROR_INSTALLMENT ) );
		}

		if ( WC_Unlimint_Constants::BANKCARD_GATEWAY !== $gateway || WC_Unlimint_Constants::PAYMENT_TYPE_PAYMENT !== $payment_type_field ) {
			throw new WC_Unlimint_Exception( __( self::ERROR_CARD ) );
		}
	}

	/**
	 * @param int $order_id
	 * @param $amount
	 *
	 * @return array
	 */
	private function get_refund_request( $order_id, $amount, $reason ) {
		return [
			'request'        => [
				'id'   => uniqid( '', true ),
				'time' => date( "Y-m-d\TH:i:s\Z" )
			],
			'merchant_order' => [
				'description' => ! empty( $reason ) ? $reason : "Refund for order #$order_id",
			],
			'payment_data'   => [
				'id' => WC_Unlimint_Helper::get_order_meta( wc_get_order( $order_id ), WC_Unlimint_Constants::ORDER_META_PAYMENT_ID_FIELDNAME )
			],
			'refund_data'    => [
				'amount'   => $amount,
				'currency' => get_woocommerce_currency()
			],
		];
	}
}