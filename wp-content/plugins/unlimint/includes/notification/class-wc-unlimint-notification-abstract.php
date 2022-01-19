<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';

abstract class WC_Unlimint_Notification_Abstract {
	const UNLIMINT_PREFIX = 'Unlimint: ';

	/**
	 * Unlimint Module
	 *
	 * @var WC_Unlimint_Module
	 */
	public $module;

	/**
	 * Is sandbox?
	 *
	 * @var true
	 */
	public $sandbox;

	/**
	 * Unlimint Log
	 *
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	/**
	 * Self!
	 *
	 * @var WC_Unlimint_Gateway_Abstract
	 */
	public $payment;

	/**
	 * @param WC_Unlimint_Gateway_Abstract $payment payment class.
	 */
	public function __construct( $payment ) {
		$this->payment = $payment;
		$this->module  = $payment->unlimint_sdk;
		$this->logger  = $payment->logger;
		$this->sandbox = $payment->sandbox;
		$this->payment = $payment;

		add_action( 'woocommerce_api_' . strtolower( get_class( $payment ) ), [ $this, 'check_action_response' ] );
		add_action( 'woocommerce_api_' . strtolower( str_ireplace( '_gateway', 'Gateway', get_class( $payment ) ) ), [
			$this,
			'check_action_response'
		] );

		add_action( 'valid_unlimint_ipn_request', [ $this, 'successful_request' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'process_cancel_order_meta_box_actions' ], 10, 1 );
	}

	/**
	 * @param string $ul_status Status.
	 *
	 * @return string|string[]
	 */
	public static function get_wc_status_for_ul_status( $ul_status ) {
		$ul_status = strtolower( $ul_status );

		$defaults = [
			'pending'     => 'pending',
			'approved'    => 'processing',
			'inprocess'   => 'on_hold',
			'inmediation' => 'on_hold',
			'rejected'    => 'failed',
			'cancelled'   => 'cancelled',
			'refunded'    => 'refunded',
			'chargedback' => 'refunded',
		];
		$status   = $defaults[ $ul_status ];

		return str_replace( '_', '-', $status );
	}

	public function check_ipn_response() {
		@ob_clean();
		$this->logger->info( __FUNCTION__, 'received _get content: ' . wp_json_encode( $_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @param array $data Preference data.
	 *
	 * @return bool|WC_Order|WC_Order_Refund
	 */
	public function successful_request( $data ) {
		$this->logger->info( __FUNCTION__, 'starting to process update...' );

		$order_id = 0;
		if ( isset( $data['merchant_order']['id'] ) ) {
			$order_id = $data['merchant_order']['id'];
		}

		if ( empty( $order_id ) ) {
			$this->logger->error( __FUNCTION__, 'External Reference not found' );
			$this->set_response( 422, null, 'External Reference not found' );
		}

		$invoice_prefix = get_option( '_ul_store_identificator', 'WC-' );
		$id             = (int) str_replace( $invoice_prefix, '', $order_id );

		$order = wc_get_order( $id );
		if ( ! $order ) {
			$this->logger->error( __FUNCTION__, 'Order is invalid' );
			$this->set_response( 422, null, 'Order is invalid' );
		}

		if ( $order->get_id() !== $id ) {
			$this->logger->error( __FUNCTION__, 'Order error' );
			$this->set_response( 422, null, 'Order error' );
		}

		$this->logger->info( __FUNCTION__, 'updating metadata and status with data: ' . wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		return $order;
	}

	/**
	 * @param string $processed_status Status.
	 * @param array $data Payment data.
	 * @param object $order Order.
	 *
	 * @throws WC_Unlimint_Exception Invalid status response.
	 */
	public function proccess_status( $processed_status, $data, $order ) {
		$used_gateway = get_class( $this->payment );

		switch ( strtolower( $processed_status ) ) {
			case 'authorized': //Transaction was successfully authorized, but needs some time to be verified, amount was held and can be captured later
			case 'completed' : //Transaction was successfully completed, amount was captured
			case 'chargeback_resolved': //Customer's chargeback claim was rejected, equals to COMPLETED
				$this->ul_rule_approved( $data, $order, $used_gateway );
				break;

			case 'new': //Transaction was submitted to Unlimint payment system and created successfully
			case 'pending':
				$this->ul_rule_pending( $data, $order, $used_gateway );
				break;

			case 'in_process': //Transaction is being processed
				$this->ul_rule_in_process( $data, $order );
				break;

			case 'declined': //Transaction was rejected
			case 'terminated' : //Payment was not executed, only 3-D Secure passed
				$this->ul_rule_rejected( $data, $order );
				break;

			case 'refunded': //Transaction was fully refunded
				$this->ul_rule_refunded( $order );
				break;

			case 'cancelled': //Transaction was cancelled by customer
				$this->ul_rule_cancelled( $data, $order );
				break;

			case 'voided': //Transaction was voided
				$this->ul_rule_in_mediation( $order );
				break;

			case 'charged_back': //Customer's chargeback claim was received
				$this->ul_rule_charged_back( $order );
				break;

			default:
				throw new WC_Unlimint_Exception( 'Process Status - Invalid Status: ' . $processed_status );
		}
	}

	/**
	 * Rule of approved payment
	 *
	 * @param array $data Payment data.
	 * @param object $order Order.
	 * @param string $used_gateway Class of gateway.
	 */
	public function ul_rule_approved( $data, $order, $used_gateway ) {
		$payment_completed_status = apply_filters(
			'woocommerce_payment_complete_order_status',
			$order->needs_processing() ? 'processing' : 'completed',
			$order->get_id(),
			$order
		);

		if ( method_exists( $order, 'get_status' ) && $order->get_status() !== 'completed' ) {
			switch ( $used_gateway ) {
				case WC_Unlimint_Constants::BANKCARD_GATEWAY:
					$order->payment_complete();
					if ( 'completed' !== $payment_completed_status ) {
						$order->add_order_note( self::UNLIMINT_PREFIX . __( 'Payment approved.', 'unlimint' ) );
						$order->update_status( self::get_wc_status_for_ul_status( 'approved' ) );
					}
					break;

				case WC_Unlimint_Constants::BOLETO_GATEWAY:
				case WC_Unlimint_Constants::PIX_GATEWAY:
					if ( 'no' === get_option( 'stock_reduce_mode', 'no' ) ) {
						$order->payment_complete();
						if ( 'completed' !== $payment_completed_status ) {
							$order->add_order_note( self::UNLIMINT_PREFIX . __( 'Payment approved.', 'unlimint' ) );
							$order->update_status( self::get_wc_status_for_ul_status( 'approved' ) );
						}
					}
					break;

				default:
					break;
			}
		}
	}

	/**
	 * Rule of pending
	 *
	 * @param array $data Payment data.
	 * @param object $order Order.
	 * @param string $used_gateway Gateway Class.
	 */
	public function ul_rule_pending( $data, $order, $used_gateway ) {
		if ( $this->can_update_order_status( $order ) ) {
			$order->update_status( self::get_wc_status_for_ul_status( 'pending' ) );

			$order->add_order_note(
				self::UNLIMINT_PREFIX . __( 'Waiting for the payment', 'unlimint' )
			);
		} else {
			$this->validate_order_note_type( $data, $order, 'pending' );
		}
	}

	/**
	 * Rule of In Process
	 *
	 * @param array $data Payment data.
	 * @param object $order Order.
	 */
	public function ul_rule_in_process( $data, $order ) {
		if ( $this->can_update_order_status( $order ) ) {
			$order->update_status(
				self::get_wc_status_for_ul_status( 'inprocess' ),
				self::UNLIMINT_PREFIX . __( 'Payment is pending review.', 'unlimint' )
			);
		} else {
			$this->validate_order_note_type( $data, $order, 'in_process' );
		}
	}

	/**
	 * Rule of Rejected
	 *
	 * @param array $data Payment data.
	 * @param object $order Order.
	 */
	public function ul_rule_rejected( $data, $order ) {
		if ( $this->can_update_order_status( $order ) ) {
			$order->update_status(
				self::get_wc_status_for_ul_status( 'rejected' ),
				self::UNLIMINT_PREFIX . __( 'Payment was declined. The customer can try again.', 'unlimint' )
			);
		} else {
			$this->validate_order_note_type( $data, $order, 'rejected' );
		}
	}

	/**
	 * Rule of Refunded
	 *
	 * @param object $order Order.
	 */
	public function ul_rule_refunded( $order ) {
		$order->update_status(
			self::get_wc_status_for_ul_status( 'refunded' ),
			self::UNLIMINT_PREFIX . __( 'Payment was returned to the customer.', 'unlimint' )
		);
	}

	/**
	 * Rule of Cancelled
	 *
	 * @param array $data Payment data.
	 * @param object $order Order.
	 */
	public function ul_rule_cancelled( $data, $order ) {
		if ( $this->can_update_order_status( $order ) ) {
			$order->update_status(
				self::get_wc_status_for_ul_status( 'cancelled' ),
				self::UNLIMINT_PREFIX . __( 'Payment was canceled.', 'unlimint' )
			);
		} else {
			$this->validate_order_note_type( $data, $order, 'cancelled' );
		}
	}

	/**
	 * Rule of In mediation
	 *
	 * @param object $order Order.
	 */
	public function ul_rule_in_mediation( $order ) {
		$order->update_status( self::get_wc_status_for_ul_status( 'inmediation' ) );
		$order->add_order_note(
			self::UNLIMINT_PREFIX . __( 'The payment is in mediation or the purchase was unknown by the customer.', 'unlimint' )
		);
	}

	/**
	 * Rule of Charged back
	 *
	 * @param object $order Order.
	 */
	public function ul_rule_charged_back( $order ) {
		$order->update_status( self::get_wc_status_for_ul_status( 'chargedback' ) );

		$order->add_order_note(
			self::UNLIMINT_PREFIX . __(
				'The payment is in mediation or the purchase was unknown by the customer.',
				'unlimint'
			)
		);
	}

	/**
	 * Process cancel Order
	 *
	 * @param object $order Order.
	 */
	public function process_cancel_order_meta_box_actions( $order ) {
		$wc_order     = wc_get_order( $order );
		$used_gateway = WC_Unlimint_Helper::get_order_meta( $wc_order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME );
		$payments     = WC_Unlimint_Helper::get_order_meta( $wc_order, '_Unlimint_Payment_IDs' );

		if ( WC_Unlimint_Constants::BANKCARD_GATEWAY === $used_gateway ) {
			return;
		}

		$this->logger->info( __FUNCTION__, 'cancelling payments for ' . $payments );

		// Canceling the order and all of its payments.
		if ( null !== $this->module && ! empty( $payments ) ) {
			$payment_ids = explode( ', ', $payments );
			foreach ( $payment_ids as $p_id ) {
				$response = $this->module->cancel_payment( $p_id );
				$status   = $response['status'];
				$this->logger->info( __FUNCTION__, 'cancel payment of id ' . $p_id . ' => ' . ( $status >= 200 && $status < 300 ? 'SUCCESS' : 'FAIL - ' . $response['response']['message'] ) );
			}
		} else {
			$this->logger->error( __FUNCTION__, 'no payments or credentials invalid' );
		}
	}

	/**
	 * @param object $order Order.
	 *
	 * @return bool
	 */
	protected function can_update_order_status( $order ) {
		return method_exists( $order, 'get_status' ) && $order->get_status() !== 'completed' && $order->get_status() !== 'processing';
	}

	/**
	 * Validate order note by type
	 *
	 * @param array $data Payment Data.
	 * @param object $order Order.
	 * @param string $status Status.
	 */
	protected function validate_order_note_type( $data, $order, $status ) {
		$payment_id = $data['id'];

		if ( isset( $data['ipn_type'] ) && 'merchant_order' === $data['ipn_type'] ) {
			$payments = [];
			foreach ( $data['payments'] as $payment ) {
				$payments[] = $payment['id'];
			}

			$payment_id = implode( ',', $payments );
		}

		$order->add_order_note(
			sprintf(
				__( 'Unlimint: The payment %1$s was notified by Unlimint with status %2$s.', 'unlimint' ),
				$payment_id,
				$status
			)
		);
	}

	/**
	 * Set response
	 *
	 * @param int $code HTTP Code.
	 * @param string $code_message Message.
	 * @param string $body Body.
	 */
	public function set_response( $code, $code_message, $body ) {
		status_header( $code, $code_message );
		die( $body );
	}
}
