<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';

abstract class WC_Unlimit_Notification_Abstract {
	const UNLIMIT_PREFIX = 'Unlimit: ';

	/**
	 * Unlimit Module
	 *
	 * @var WC_Unlimit_Module
	 */
	public $module;

	/**
	 * Is sandbox?
	 *
	 * @var true
	 */
	public $sandbox;

	/**
	 * Unlimit Log
	 *
	 * @var WC_Unlimit_Logger
	 */
	public $logger;

	/**
	 * Self!
	 *
	 * @var WC_Unlimit_Gateway_Abstract
	 */
	public $payment;

	/**
	 * @param WC_Unlimit_Gateway_Abstract $payment payment class.
	 */
	public function __construct( $payment ) {
		$this->payment = $payment;
		$this->module  = $payment->unlimit_sdk;
		$this->logger  = $payment->logger ?? new WC_Unlimit_Logger();
		$this->sandbox = $payment->sandbox;

		add_action( 'woocommerce_api_' . strtolower( get_class( $payment ) ), [ $this, 'check_action_response' ] );
		add_action( 'woocommerce_api_' . strtolower( str_ireplace( '_gateway', 'Gateway', get_class( $payment ) ) ), [
			$this,
			'check_action_response'
		] );

		add_action( 'valid_unlimit_ipn_request', [ $this, 'successful_request' ] );
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

	public function check_in_response() {
		@ob_clean();
		$this->logger->info( __FUNCTION__,
			'received _get content: ' . wp_json_encode( $_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
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

		$this->logger->info( __FUNCTION__,
			'updating metadata and status with data: ' . wp_json_encode( $data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		return $order;
	}

	/**
	 * @param string $processed_status Status.
	 * @param array $data Payment data.
	 * @param object $order Order.
	 *
	 * @throws WC_Unlimit_Exception Invalid status response.
	 */
	public function proccess_status( $processed_status, $data, $order ) {
		$used_gateway = get_class( $this->payment );

		switch ( strtolower( $processed_status ) ) {
			case 'authorized':
			case 'completed' : //Transaction was successfully completed, amount was captured
			case 'chargeback_resolved': //Customer's chargeback claim was rejected, equals to COMPLETED
				$this->ul_rule_approved( $order, $used_gateway );
				break;

			case 'new': //Transaction was submitted to Unlimit payment system and created successfully
			case 'pending':
				$this->ul_rule_pending( $data, $order );
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
				throw new WC_Unlimit_Exception( 'Process Status - Invalid Status: ' . $processed_status );
		}
	}

	/**
	 * Rule of approved payment
	 *
	 * @param object $order Order.
	 * @param string $used_gateway Class of gateway.
	 */
	public function ul_rule_approved( $order, $used_gateway ) {
		$payment_completed_status = apply_filters(
			'woocommerce_payment_complete_order_status',
			$order->needs_processing() ? 'processing' : 'completed',
			$order->get_id(),
			$order
		);

		if ( method_exists( $order, 'get_status' ) && $order->get_status() !== 'completed' ) {
			switch ( $used_gateway ) {
				case WC_Unlimit_Constants::BANKCARD_GATEWAY:
					$order->payment_complete();
					if ( 'completed' !== $payment_completed_status ) {
						$order->add_order_note( self::UNLIMIT_PREFIX . __( 'Payment approved.', 'unlimit' ) );
						$order->update_status( self::get_wc_status_for_ul_status( 'approved' ) );
					}
					break;

				case WC_Unlimit_Constants::AIRTELTZ_GATEWAY:
				case WC_Unlimit_Constants::APAY_GATEWAY:
				case WC_Unlimit_Constants::BOLETO_GATEWAY:
				case WC_Unlimit_Constants::PAYPAL_GATEWAY:
				case WC_Unlimit_Constants::SPEI_GATEWAY:
				case WC_Unlimit_Constants::MBWAY_GATEWAY:
				case WC_Unlimit_Constants::SEPA_GATEWAY:
				case WC_Unlimit_Constants::GPAY_GATEWAY:
				case WC_Unlimit_Constants::PIX_GATEWAY:
				case WC_Unlimit_Constants::MULTIBANCO_GATEWAY:
				case WC_Unlimit_Constants::OXXO_GATEWAY:
					if ( 'no' === get_option( 'stock_reduce_mode', 'no' ) ) {
						$order->payment_complete();
						if ( 'completed' !== $payment_completed_status ) {
							$order->add_order_note( self::UNLIMIT_PREFIX . __( 'Payment approved.', 'unlimit' ) );
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
	 */
	public function ul_rule_pending( $data, $order ) {
		if ( $this->can_update_order_status( $order ) ) {
			$order->update_status( self::get_wc_status_for_ul_status( 'pending' ) );

			$order->add_order_note(
				self::UNLIMIT_PREFIX . __( 'Waiting for the payment', 'unlimit' )
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
				self::UNLIMIT_PREFIX . __( 'Payment is pending review.', 'unlimit' )
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
				self::UNLIMIT_PREFIX . __( 'Payment was declined. The customer can try again.', 'unlimit' )
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
			self::UNLIMIT_PREFIX . __( 'Payment was returned to the customer.', 'unlimit' )
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
				self::UNLIMIT_PREFIX . __( 'Payment was canceled.', 'unlimit' )
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
			self::UNLIMIT_PREFIX . __( 'The payment is in mediation or the purchase was unknown by the customer.',
				'unlimit' )
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
			self::UNLIMIT_PREFIX . __(
				'The payment is in mediation or the purchase was unknown by the customer.',
				'unlimit'
			)
		);
	}

	/**
	 * Process cancel Order
	 *
	 * @param object $order Order.
	 */
	public function process_cancel_order_meta_box_actions( $order ) {
		$wc_order    = wc_get_order( $order );
		$usedGateway = WC_Unlimit_Helper::get_order_meta( $wc_order,
			WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME );
		$payments    = WC_Unlimit_Helper::get_order_meta( $wc_order, '_Unlimit_Payment_IDs' );

		if ( WC_Unlimit_Constants::BANKCARD_GATEWAY === $usedGateway ) {
			return;
		}

		$this->logger->info( __FUNCTION__, 'cancelling payments for ' . $payments );

		// Canceling the order and all of its payments.
		if ( null !== $this->module && ! empty( $payments ) ) {
			$payment_ids = explode( ', ', $payments );
			foreach ( $payment_ids as $p_id ) {
				$response = $this->module->cancel_payment( $p_id );
				$status   = $response['status'];
				$this->logger->info( __FUNCTION__,
					'cancel payment of id ' . $p_id . ' => ' . (
					$status >= 200 && $status < 300
						? 'SUCCESS'
						: 'FAIL - ' . $response['response']['message']
					)
				);
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
		return method_exists(
			       $order,
			       'get_status'
		       ) &&
		       $order->get_status() !== 'completed' &&
		       $order->get_status() !== 'processing';
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
				__( 'Unlimit: The payment %1$s was notified by Unlimit with status %2$s.', 'unlimit' ),
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
