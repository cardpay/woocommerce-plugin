<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Overrides\Order;

require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';
require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Order_Status_Fields.php';
require_once __DIR__ . '/WC_Unlimit_Files_Registrar.php';

/**
 * Authorized (2 phase) payment or installment
 */
class WC_Unlimit_Auth_Payment {

	public const CAPTURE_PAYMENT_ACTION = 'capture';
	public const CANCEL_PAYMENT_ACTION = 'cancel';
	public const COMPLETE_STATUS_TO = 'COMPLETE';
	public const REVERSE_STATUS_TO = 'REVERSE';

	private const ERROR_RESPONSE = [ 'success' => false ];
	private const SUCCESSFUL_RESPONSE = [ 'success' => true ];
	private const PAYMENT_DATA = 'payment_data';

	protected $files_registrar;

	/**
	 * @var WC_Unlimit_Logger
	 */
	public $logger;

	/**
	 * @var WC_Unlimit_Sdk
	 */
	public $unlimit_sdk;

	public function __construct() {
		$this->logger          = new WC_Unlimit_Logger();
		$this->unlimit_sdk     = new WC_Unlimit_Sdk( WC_Unlimit_Custom_Gateway::GATEWAY_ID );
		$this->files_registrar = new WC_Unlimit_Files_Registrar();
	}

	// called in UnlimitInit
	public function ajax_capture() {
		return $this->do_payment_action( self::CAPTURE_PAYMENT_ACTION );
	}

	// called in UnlimitInit
	public function ajax_cancel() {
		return $this->do_payment_action( self::CANCEL_PAYMENT_ACTION );
	}

	/**
	 * @param Order $order
	 *
	 * @return void
	 */
	public function show_auth_payment_buttons( $order ) {
		$this->files_registrar->load_order_actions();
		$callback_status = WC_Unlimit_Helper::get_order_meta(
			$order,
			WC_Unlimit_Constants::ORDER_META_CALLBACK_STATUS_FIELDNAME
		);
		if (
			! is_admin() ||
			is_null( $order ) ||
			'on-hold' !== $order->get_status() ||
			WC_Unlimit_Constants::TRANSACTION_STATUS_CHARGED_BACK === $callback_status
		) {
			return;
		}

		$preauth = WC_Unlimit_Helper::get_order_meta( $order, WC_Unlimit_Constants::ORDER_META_PREAUTH_FIELDNAME );
		$gateway = WC_Unlimit_Helper::get_order_meta( $order, WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME );
		if ( empty( $preauth ) || 'true' !== $preauth || WC_Unlimit_Constants::BANKCARD_GATEWAY !== $gateway ) {
			return;
		}

		$this->files_registrar->load_order_actions();
	}

	public function do_payment_action( $payment_action ) {
		check_ajax_referer( 'order-item', 'security' );
		if ( ! is_admin() || ! current_user_can( 'edit_shop_orders' ) || empty( $_POST['order_id'] ) ) {
			return self::ERROR_RESPONSE;
		}

		$order_id = (int) $_POST['order_id'];
		$order    = wc_get_order( $order_id );
		$gateway  = WC_Unlimit_Helper::get_order_meta( $order, WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME );
		if ( WC_Unlimit_Constants::BANKCARD_GATEWAY !== $gateway ) {
			return self::ERROR_RESPONSE;
		}

		$response = self::ERROR_RESPONSE;
		switch ( $payment_action ) {
			case self::CAPTURE_PAYMENT_ACTION:
				$response = $this->capture_payment( $order, $order_id );
				break;

			case self::CANCEL_PAYMENT_ACTION:
				$is_api_transaction_updated = $this->update_api_transaction_status( $order, self::REVERSE_STATUS_TO );
				if ( $is_api_transaction_updated ) {
					$order->set_status( WC_Unlimit_Admin_Order_Status_Fields::CANCELLED_WC );
					$order->save();

					$this->logger->info(
						__FUNCTION__,
						"Order #$order_id: payment was cancelled and new order status 'Cancelled' has been set"
					);
					$response = self::SUCCESSFUL_RESPONSE;
				}
				break;

			default:
				$this->logger->error( __FUNCTION__, "Invalid auth payment action: '$payment_action' for order #$order_id" );
				break;
		}

		return $response;
	}

	/**
	 * @param WC_Order $order
	 * @param int $order_id
	 *
	 * @return array|bool[]
	 */
	private function capture_payment( $order, $order_id ) {
		$order_total = $order->get_total();
		if ( $order_total <= 0 ) {
			return $this->get_error_response(
				__(
					'Order total amount must be more than 0 to capture the payment',
					'unlimit'
				),
				$order_id
			);
		}

		$initial_order_amount = WC_Unlimit_Helper::get_order_meta(
			$order,
			WC_Unlimit_Constants::ORDER_META_INITIAL_API_TOTAL
		);
		if ( ! is_null( $initial_order_amount ) && $order_total > (float) $initial_order_amount ) {
			return $this->get_error_response(
				__(
					"Order total amount must not exceed the blocked amount (",
					'unlimit'
				) .
				$initial_order_amount .
				__(
					") to capture the payment",
					'unlimit'
				),
				$order_id
			);
		}

		$response                   = self::ERROR_RESPONSE;
		$is_api_transaction_updated = $this->update_api_transaction_status( $order, self::COMPLETE_STATUS_TO );
		if ( $is_api_transaction_updated ) {
			$order->set_status( WC_Unlimit_Admin_Order_Status_Fields::PROCESSING_WC );
			$order->save();

			$this->logger->info(
				__FUNCTION__,
				"Order #$order_id: payment was captured and new order status 'Processing' has been set"
			);
			$response = self::SUCCESSFUL_RESPONSE;
		}

		return $response;
	}

	private function get_error_response( $log_error_message, $order_id ) {
		$error_message = __( $log_error_message, 'unlimit' );
		$this->logger->error( __FUNCTION__, "$error_message, for order #$order_id" );

		return [
			'success' => false,
			'data'    => [
				'error_message' => (string) $error_message
			]
		];
	}

	/**
	 * @param WC_Order $order
	 * @param string $status_to
	 *
	 * @return bool
	 */
	private function update_api_transaction_status( $order, $status_to ) {
		$order_id           = $order->get_id();
		$amount             = $order->get_total();
		$payment_type_field = WC_Unlimit_Helper::get_order_meta(
			$order,
			WC_Unlimit_Constants::ORDER_META_PAYMENT_TYPE_FIELDNAME
		);
		$payment_id         = $order->get_transaction_id();

		if ( $payment_type_field === WC_Unlimit_Constants::PAYMENT_TYPE_PAYMENT ) {
			$api_request = $this->get_api_request_for_update( self::PAYMENT_DATA, $status_to, $amount );
			$endpoint    = "/payments/$payment_id";
		} else {
			$this->logger->error( __FUNCTION__, "Invalid payment type: '$payment_type_field' for order #$order_id" );

			return false;
		}

		$api_response = $this->unlimit_sdk->patch( $endpoint, wp_json_encode( $api_request ) );
		if ( ! is_array( $api_response )
		     || empty( $api_response )
		     || (int) $api_response['status'] !== 200
		     || ! isset( $api_response['response'][ self::PAYMENT_DATA ]['status'] ) ) {
			$this->logger->error( __FUNCTION__, "Unable to update Unlimit transaction '$payment_id' for order #$order_id" );

			return false;
		}

		return $this->is_payment_status_updated(
			$payment_type_field,
			$api_response['response'][ self::PAYMENT_DATA ],
			$status_to
		);
	}

	/**
	 * @param string $payment_type_field
	 * @param array $api_structure
	 * @param string $status_to
	 *
	 * @return bool
	 */
	private function is_payment_status_updated( $payment_type_field, $api_structure, $status_to ) {
		$status = $api_structure['status'] ?? '';

		$is_payment_status_updated = false;

		switch ( $status_to ) {
			case self::COMPLETE_STATUS_TO:
				if (
					WC_Unlimit_Constants::PAYMENT_TYPE_PAYMENT === $payment_type_field &&
					WC_Unlimit_Constants::TRANSACTION_STATUS_COMPLETED === $status
				) {
					$is_payment_status_updated = true;
				}
				break;

			case self::REVERSE_STATUS_TO:
				$is_payment_status_updated = ( WC_Unlimit_Constants::TRANSACTION_STATUS_VOIDED === $status );
				break;

			default:
				break;
		}

		return $is_payment_status_updated;
	}

	private function get_api_request_for_update( $api_structure, $status_to, $amount ) {
		$api_request = [
			'request'      => [
				'id'   => uniqid( '', true ),
				'time' => date( "Y-m-d\TH:i:s\Z" )
			],
			'operation'    => 'CHANGE_STATUS',
			$api_structure => [
				'status_to' => $status_to
			]
		];

		if ( self::COMPLETE_STATUS_TO === $status_to ) {
			$api_request[ $api_structure ]['amount'] = $amount;
		}

		return $api_request;
	}
}