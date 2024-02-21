<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/log/WC_Unlimit_Logger.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';
require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Order_Status_Fields.php';
require_once __DIR__ . '/WC_Unlimit_Order_Status_Updater.php';

class WC_Unlimit_Callback {
	const SIGNATURE_HEADER = 'HTTP_SIGNATURE';

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

			case WC_Unlimit_Constants::APAY_GATEWAY:
				$fieldname_prefix = WC_Unlimit_Admin_Apay_Fields::FIELDNAME_PREFIX;
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

		$new_order_status_option = $this->get_new_order_status_option( $new_order_status );
		$this->logger->log_callback_request( __FUNCTION__, 'Unlimit callback: Order #' .
		                                                   $order_id . ' status was updated to: ' . $new_order_status );

		$order_statuses = wc_get_order_statuses();
		if ( ! in_array( $new_order_status_option, array_keys( $order_statuses ) ) ) {
			$this->logger->log_callback_request( __FUNCTION__,
				"Order status '$new_order_status_option' does not exist!" );
		}

		$order_status_updater = new WC_Unlimit_Order_Status_Updater();
		$order                = wc_get_order( $order_id );
		$order_status_updater->update_order_status( $order, $new_order_status_option );

		WC_Unlimit_Helper::set_order_meta( $order,
			WC_Unlimit_Constants::ORDER_META_CALLBACK_STATUS_FIELDNAME, $new_order_status_option );

		if ( $transaction_id && ! $order->get_transaction_id() ) {
			$order->set_transaction_id( $transaction_id );
		}

		$this->logger->log_callback_request( __FUNCTION__, "Order #$order_id save" );
		$order->save();
	}

	private function get_new_order_status_option( $new_order_status ) {
		return get_option(
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
			$this->logger->log_callback_request(
				__FUNCTION__,
				"Unlimit, order #$order_id: callback for partial refund is ignored. Order status wasn't changed."
			);

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
			$transaction_type = WC_Unlimit_Constants::PAYMENT_DATA;
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
		       && isset( $callback_decoded[ WC_Unlimit_Constants::PAYMENT_DATA ]['remaining_amount'] )
		       && ( 0 === (int) $callback_decoded[ WC_Unlimit_Constants::PAYMENT_DATA ]['remaining_amount'] );
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
	 * @param  mixed  $callback_decoded
	 *
	 * @return mixed|string
	 */
	private function getOrder_id( array $callback_decoded ): string {
		return $callback_decoded['merchant_order']['id'] ?? 'N/A';
	}
}