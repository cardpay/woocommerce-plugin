<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/log/WC_Unlimit_Logger.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';
require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Order_Status_Fields.php';

class WC_Unlimit_Callback {
	const SIGNATURE_HEADER = 'HTTP_SIGNATURE';

	private const PAYMENT_DATA_TRANSACTION_TYPE = 'payment_data';
	private const REFUND_DATA_TRANSACTION_TYPE = 'refund_data';
	private const STATUS_FIELD = 'status';

	/**
	 * @var WC_Unlimit_Logger
	 */
	public $logger;

	public function __construct() {
		$this->logger = new WC_Unlimit_Logger();
	}

	public function process_callback() {
		$this->logger->log_callback_request( __FUNCTION__, 'Callback processing has started' );

		$is_valid_signature = $this->is_valid_signature();
		if ( ! $is_valid_signature ) {
			http_response_code( 400 );
			wp_send_json_error( [ 'message' => 'Invalid signature' ], 400 );

			return;
		}

		$callback         = $this->get_callback_body();
		$callback_decoded = $this->decode_callback( $callback );

		$order_id     = $this->getOrder_id( $callback_decoded );
		$payment_type = $this->get_transaction_type( $callback_decoded );

		$this->logger->log_callback_request(
			__FUNCTION__,
			"Unlimit callback for order #$order_id (Payment Type: $payment_type, Payment ID:
			 {$callback_decoded[$payment_type]['id']}): " . print_r( $callback_decoded, true )
		);

		$this->set_order_status( $callback );

		echo 'OK';
	}


	/**
	 * @throws JsonException
	 */
	public function is_valid_signature() {
		try {
			$callback_secret = $this->get_callback_secret();
		} catch ( WC_Unlimit_Exception $e ) {
			$this->logger->error( __FUNCTION__, 'Invalid signature: ' . $e->getMessage() );

			return false;
		}

		$callback            = $this->get_callback_body();
		$callback_signature  = $_SERVER[ self::SIGNATURE_HEADER ];
		$generated_signature = hash( 'sha512', $callback . $callback_secret );

		$callback_decoded = $this->decode_callback( $callback );

		$is_valid_signature = true;
		if ( $generated_signature !== $callback_signature ) {
			$order_id = $this->getOrder_id( $callback_decoded );

			$this->logger->error( __FUNCTION__, 'Unlimit callback signature does not match for order #' .
			                                    $order_id );
			$is_valid_signature = false;
		}

		return $is_valid_signature;
	}

	/**
	 * @throws WC_Unlimit_Exception
	 * @throws JsonException
	 */
	private function get_callback_secret() {
		if ( ! isset( $_SERVER[ self::SIGNATURE_HEADER ] ) ) {
			throw new WC_Unlimit_Exception( 'Unlimit callback signature is not set' );
		}

		$callback         = $this->get_callback_body();
		$callback_decoded = $this->decode_callback( $callback );
		if ( ! isset( $callback_decoded['merchant_order']['id'] ) ) {
			throw new WC_Unlimit_Exception( 'Invalid Unlimit callback' );
		}

		$gateway_class = $this->get_gateway_class( $callback_decoded['merchant_order']['id'] );
		switch ( $gateway_class ) {
			case WC_Unlimit_Constants::BANKCARD_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::BOLETO_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Boleto_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::PIX_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Pix_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::MBWAY_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Mbway_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::PAYPAL_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Paypal_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::SPEI_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Spei_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::SEPA_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Sepa_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::MULTIBANCO_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Multibanco_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Constants::GPAY_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Gpay_Fields::FIELDNAME_PREFIX;
				break;

			default:
				throw new WC_Unlimit_Exception( 'Invalid gateway provided for Unlimit callback secret' );
		}

		return get_option( $fieldname_prefix . WC_Unlimit_Admin_Fields::FIELD_CALLBACK_SECRET );
	}

	/**
	 * @param $callback
	 *
	 * @throws JsonException
	 * @throws WC_Data_Exception
	 * @throws WC_Unlimit_Exception
	 */
	private function set_order_status( $callback ) {
		if ( ! $this->is_valid_callback( $callback ) ) {
			return;
		}

		$callback_decoded = $this->decode_callback( $callback );
		$order_id         = $this->getOrder_id( $callback_decoded );
		$transaction_type = $this->get_transaction_type( $callback_decoded );
		$transaction_id   = $this->get_transaction_id( $callback_decoded, $transaction_type );
		$new_order_status = $this->get_new_order_status( $callback_decoded, $transaction_type );

		$this->logger->log_callback_request(
			__FUNCTION__,
			"Unlimit new status for order #$order_id: $new_order_status (Payment Type: $transaction_type)"
		);

		$new_order_status_option = $this->get_new_order_status_option( $order_id, $new_order_status );
		$this->logger->log_callback_request( __FUNCTION__, 'Unlimit callback: Order #' .
		                                                   $order_id . ' status was updated to: ' . $new_order_status );

		if ( ! in_array( $new_order_status_option, wc_get_order_statuses() ) ) {
			$this->logger->log_callback_request( __FUNCTION__,
				"Order status '$new_order_status_option' does not exist!" );
		}

		$order = wc_get_order( $order_id );
		$this->logger->log_callback_request( __FUNCTION__, "Before updating order status for order #$order_id" );
		$status_change_info = $order->set_status( $new_order_status_option );

		if ( $status_change_info ) {
			$this->logger->log_callback_request(
				__FUNCTION__,
				"Unlimit callback was processed successfully, order #$order_id,
				 new status: $new_order_status (Payment Type: $transaction_type)"
			);
		} else {
			$this->logger->error( __FUNCTION__, 'Failed to update order status for order #' . $order_id );
		}

		WC_Unlimit_Helper::set_order_meta( $order,
			WC_Unlimit_Constants::ORDER_META_CALLBACK_STATUS_FIELDNAME, $new_order_status_option );
		if ( $transaction_id && ! $order->get_transaction_id() ) {
			$order->set_transaction_id( $transaction_id );
		}
		$order->save();

		$old_status     = $status_change_info['from'];
		$new_status_set = $status_change_info['to'];

		$this->logger->log_callback_request(
			__FUNCTION__,
			"Unlimit callback was processed, order #$order_id, old status: $old_status, new status: $new_status_set"
		);
	}

	private function get_new_order_status_option( $order_id, $new_order_status ) {
		$gateway_class = $this->get_gateway_class( $order_id );

		switch ( $gateway_class ) {
			case WC_Unlimit_Constants::BANKCARD_GATEWAY:
				$gateway = new WC_Unlimit_Custom_Gateway();
				break;

			case WC_Unlimit_Constants::BOLETO_GATEWAY:
				$gateway = new WC_Unlimit_Ticket_Gateway();
				break;

			case WC_Unlimit_Constants::PIX_GATEWAY:
				$gateway = new WC_Unlimit_Pix_Gateway();
				break;

			case WC_Unlimit_Constants::PAYPAL_GATEWAY:
				$gateway = new WC_Unlimit_Paypal_Gateway();
				break;

			case WC_Unlimit_Constants::SPEI_GATEWAY:
				$gateway = new WC_Unlimit_Spei_Gateway();
				break;

			case WC_Unlimit_Constants::MBWAY_GATEWAY:
				$gateway = new WC_Unlimit_Mbway_Gateway();
				break;

			case WC_Unlimit_Constants::SEPA_GATEWAY:
				$gateway = new WC_Unlimit_Sepa_Gateway();
				break;

			case WC_Unlimit_Constants::MULTIBANCO_GATEWAY:
				$gateway = new WC_Unlimit_Multibanco_Gateway();
				break;

			case WC_Unlimit_Constants::GPAY_GATEWAY:
				$gateway = new WC_Unlimit_Gpay_Gateway();
				break;

			default:
				throw new WC_Unlimit_Exception( 'Unable to get new order status from Unlimit callback' );
		}

		return $gateway->get_option(
			WC_Unlimit_Admin_Order_Status_Fields::FIELDNAME_PREFIX .
			strtolower( $new_order_status )
		);
	}

	private function is_valid_callback( $callback ) {
		$callback_decoded = $this->decode_callback( $callback );
		if ( empty( $callback_decoded['merchant_order'] ) || empty( $callback_decoded['merchant_order']['id'] ) ) {
			$this->logger->error( __FUNCTION__, 'Unlimit callback: order id is not set' );

			return false;
		}

		$order_id = $callback_decoded['merchant_order']['id'];

		if ( $this->is_refund( $callback_decoded ) && ! $this->is_full_refund( $callback_decoded ) ) {
			$this->logger->log_callback_request( __FUNCTION__,
				"Unlimit, order #$order_id: callback for partial refund is ignored" );

			return false;
		}

		$is_valid_callback = true;
		$transaction_type  = $this->get_transaction_type( $callback_decoded );
		if (
			empty( $callback_decoded[ $transaction_type ] ) ||
			empty( $callback_decoded[ $transaction_type ][ self::STATUS_FIELD ] )
		) {
			$this->logger->error( __FUNCTION__, "Unlimit callback for order #$order_id: order status is not set" );

			$is_valid_callback = false;
		}

		return $is_valid_callback;
	}

	private function get_transaction_id( $callback_decoded, $transaction_type ) {
		return $callback_decoded[ $transaction_type ]['id'] ?? '';
	}

	private function get_transaction_type( $callback_decoded ) {
		if ( isset( $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ] ) ) {
			$transaction_type = self::REFUND_DATA_TRANSACTION_TYPE;
		} else {
			$transaction_type = self::PAYMENT_DATA_TRANSACTION_TYPE;
		}

		return $transaction_type;
	}

	private function get_new_order_status( $callback_decoded, $payment_type ) {
		if ( $this->is_full_refund( $callback_decoded ) ) {
			return WC_Unlimit_Constants::TRANSACTION_STATUS_REFUNDED;
		}

		return $callback_decoded[ $payment_type ][ self::STATUS_FIELD ];
	}

	private function is_refund( $callback_decoded ) {
		return ! empty( $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ][ self::STATUS_FIELD ] );
	}

	private function is_full_refund( $callback_decoded ) {
		return $this->is_refund( $callback_decoded )
		       && WC_Unlimit_Constants::TRANSACTION_STATUS_COMPLETED ===
		          $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ][ self::STATUS_FIELD ]
		       && isset( $callback_decoded[ self::PAYMENT_DATA_TRANSACTION_TYPE ]['remaining_amount'] )
		       && ( 0 === (int) $callback_decoded[ self::PAYMENT_DATA_TRANSACTION_TYPE ]['remaining_amount'] );
	}

	private function get_callback_body() {
		return file_get_contents( 'php://input' );
	}

	private function decode_callback( $callback ) {
		return json_decode( $callback, true, 512, JSON_THROW_ON_ERROR );
	}

	/**
	 * @param $order_id
	 *
	 * @return string Gateway class name
	 */
	private function get_gateway_class( $order_id ) {
		$order = wc_get_order( $order_id );

		return WC_Unlimit_Helper::get_order_meta( $order, WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME );
	}

	/**
	 * @param mixed $callback_decoded
	 *
	 * @return mixed|string
	 */
	private function getOrder_id( mixed $callback_decoded ): mixed {
		$order_id = isset( $callback_decoded['merchant_order']['id'] ) ?
			$callback_decoded['merchant_order']['id'] : 'N/A';

		return $order_id;
	}
}