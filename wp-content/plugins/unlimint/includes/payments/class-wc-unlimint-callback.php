<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/log/class-wc-unlimint-logger.php';
require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';
require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-order-status-fields.php';

class WC_Unlimint_Callback {

	const SIGNATURE_HEADER = 'HTTP_SIGNATURE';

	private const PAYMENT_DATA_TRANSACTION_TYPE = 'payment_data';
	private const RECURRING_DATA_TRANSACTION_TYPE = 'recurring_data';
	private const REFUND_DATA_TRANSACTION_TYPE = 'refund_data';
	private const STATUS_FIELD = 'status';

	/**
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	public function __construct() {
		$this->logger = new WC_Unlimint_Logger();
	}

	public function process_callback() {
		$is_valid_signature = $this->is_valid_signature();
		if ( ! $is_valid_signature ) {
			return;
		}

		$callback = $this->get_callback_body();
		$this->logger->info( __FUNCTION__, 'Unlimint callback: ' . print_r( $callback, true ) );

		$this->set_order_status( $callback );

		echo 'OK';
	}

	/**
	 * @throws JsonException
	 */
	public function is_valid_signature() {
		try {
			$callback_secret = $this->get_callback_secret();
		} catch ( WC_Unlimint_Exception $e ) {
			$this->logger->error( __FUNCTION__, $e->getMessage() );

			return false;
		}

		$callback            = $this->get_callback_body();
		$callback_signature  = $_SERVER[ self::SIGNATURE_HEADER ];
		$generated_signature = hash( 'sha512', $callback . $callback_secret );

		$is_valid_signature = true;
		if ( $generated_signature !== $callback_signature ) {
			$this->logger->error( __FUNCTION__, 'Unlimint callback signature does not match' );

			$is_valid_signature = false;
		}

		return $is_valid_signature;
	}

	/**
	 * @throws WC_Unlimint_Exception
	 * @throws JsonException
	 */
	private function get_callback_secret() {
		if ( ! isset( $_SERVER[ self::SIGNATURE_HEADER ] ) ) {
			throw new WC_Unlimint_Exception( 'Unlimint callback signature is not set' );
		}

		$callback         = $this->get_callback_body();
		$callback_decoded = $this->decode_callback( $callback );
		if ( ! isset( $callback_decoded['merchant_order']['id'] ) ) {
			throw new WC_Unlimint_Exception( 'Invalid Unlimint callback' );
		}

		$gateway_class = $this->get_gateway_class( $callback_decoded['merchant_order']['id'] );
		switch ( $gateway_class ) {
			case WC_Unlimint_Constants::BANKCARD_GATEWAY:
				$callback_secret = get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET );
				break;

			case WC_Unlimint_Constants::BOLETO_GATEWAY:
				$callback_secret = get_option( WC_Unlimint_Admin_Boleto_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET );
				break;

			default:
				throw new WC_Unlimint_Exception( 'Unable to detect Unlimint callback secret' );
		}

		return $callback_secret;
	}

	/**
	 * @throws WC_Unlimint_Exception
	 * @throws JsonException
	 */
	private function set_order_status( $callback ) {
		if ( ! $this->is_valid_callback( $callback ) ) {
			return;
		}

		$callback_decoded = $this->decode_callback( $callback );
		$order_id         = $callback_decoded['merchant_order']['id'];
		$transaction_type = $this->get_transaction_type( $callback_decoded );
		$new_order_status = $this->get_new_order_status( $callback_decoded, $transaction_type );

		$this->logger->info( __FUNCTION__, "Unlimint new status for order #$order_id: $new_order_status" );

		$new_order_status_option = $this->get_new_order_status_option( $order_id, $new_order_status );
		$order                   = wc_get_order( $order_id );
		$status_change_info      = $order->set_status( $new_order_status_option );

		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_CALLBACK_STATUS_FIELDNAME, $new_order_status_option );
		$order->save();

		$old_status     = $status_change_info['from'];
		$new_status_set = $status_change_info['to'];

		$this->logger->info( __FUNCTION__, "Unlimint callback was processed, order #$order_id, old status: $old_status, new status: $new_status_set" );
	}

	private function get_new_order_status_option( $order_id, $new_order_status ) {
		$gateway_class = $this->get_gateway_class( $order_id );

		switch ( $gateway_class ) {
			case WC_Unlimint_Constants::BANKCARD_GATEWAY:
				$gateway = new WC_Unlimint_Custom_Gateway();
				break;

			case WC_Unlimint_Constants::BOLETO_GATEWAY:
				$gateway = new WC_Unlimint_Ticket_Gateway();
				break;

			default:
				throw new WC_Unlimint_Exception( 'Unable to get new order status from Unlimint callback' );
		}

		return $gateway->get_option( WC_Unlimint_Admin_Order_Status_Fields::FIELDNAME_PREFIX . strtolower( $new_order_status ) );
	}

	private function is_valid_callback( $callback ) {
		$callback_decoded = $this->decode_callback( $callback );
		if ( empty( $callback_decoded['merchant_order'] ) || empty( $callback_decoded['merchant_order']['id'] ) ) {
			$this->logger->error( __FUNCTION__, 'Unlimint callback: order id is not set' );

			return false;
		}

		$order_id = $callback_decoded['merchant_order']['id'];

		if ( $this->is_refund( $callback_decoded ) && ! $this->is_full_refund( $callback_decoded ) ) {
			$this->logger->info( __FUNCTION__, "Unlimint, order #$order_id: callback for partial refund is ignored" );

			return false;
		}

		$is_valid_callback = true;
		$transaction_type  = $this->get_transaction_type( $callback_decoded );
		if ( empty( $callback_decoded[ $transaction_type ] ) || empty( $callback_decoded[ $transaction_type ][ self::STATUS_FIELD ] ) ) {
			$this->logger->error( __FUNCTION__, "Unlimint callback for order #$order_id: order status is not set" );

			$is_valid_callback = false;
		}

		return $is_valid_callback;
	}

	private function get_transaction_type( $callback_decoded ) {
		$transaction_type = null;

		if ( isset( $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ] ) ) {
			$transaction_type = self::REFUND_DATA_TRANSACTION_TYPE;
		} else if ( isset( $callback_decoded[ self::PAYMENT_DATA_TRANSACTION_TYPE ] ) ) {
			$transaction_type = self::PAYMENT_DATA_TRANSACTION_TYPE;
		} else if ( isset( $callback_decoded[ self::RECURRING_DATA_TRANSACTION_TYPE ] ) ) {
			$transaction_type = self::RECURRING_DATA_TRANSACTION_TYPE;
		}

		return $transaction_type;
	}

	private function get_new_order_status( $callback_decoded, $payment_type ) {
		if ( $this->is_full_refund( $callback_decoded ) ) {
			return WC_Unlimint_Constants::TRANSACTION_STATUS_REFUNDED;
		}

		return $callback_decoded[ $payment_type ][ self::STATUS_FIELD ];
	}

	private function is_refund( $callback_decoded ) {
		return ! empty( $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ][ self::STATUS_FIELD ] );
	}

	private function is_full_refund( $callback_decoded ) {
		return $this->is_refund( $callback_decoded )
		       && WC_Unlimint_Constants::TRANSACTION_STATUS_COMPLETED === $callback_decoded[ self::REFUND_DATA_TRANSACTION_TYPE ][ self::STATUS_FIELD ]
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

		return WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME );
	}
}