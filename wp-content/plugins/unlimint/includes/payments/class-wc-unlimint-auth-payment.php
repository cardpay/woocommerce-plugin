<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Overrides\Order;

require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';
require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-order-status-fields.php';

/**
 * Authorized (2 phase) payment or installment
 */
class WC_Unlimint_Auth_Payment {

	public const GET_PARAM = 'ul_payment_action';
	public const CAPTURE_ACTION = 'capture';
	public const CANCEL_ACTION = 'cancel';
	public const COMPLETE_STATUS_TO = 'COMPLETE';
	public const REVERSE_STATUS_TO = 'REVERSE';

	/**
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	/**
	 * @var Unlimint_Sdk
	 */
	public $unlimint_sdk;

	public function __construct() {
		$this->logger       = new WC_Unlimint_Logger();
		$this->unlimint_sdk = new Unlimint_Sdk( WC_Unlimint_Custom_Gateway::GATEWAY_ID );
	}

	/**
	 * @param Order $order
	 *
	 * @return void
	 */
	public function show_auth_payment_buttons( $order ) {
		$callback_status = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_CALLBACK_STATUS_FIELDNAME );
		if ( ! is_admin() || is_null( $order ) || 'on-hold' !== $order->get_status() || WC_Unlimint_Constants::TRANSACTION_STATUS_CHARGED_BACK === $callback_status ) {
			return;
		}

		$preauth = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_PREAUTH_FIELDNAME );
		$gateway = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME );
		if ( empty( $preauth ) || 'true' !== $preauth || WC_Unlimint_Constants::BANKCARD_GATEWAY !== $gateway ) {
			return;
		}

		$capture_button_label = __( 'Capture' );
		$cancel_button_label  = __( 'Cancel' );
		echo "<button type='button' id='ul_button_capture' class='button' style='color: #000000'>$capture_button_label</button> ";
		echo "<button type='button' id='ul_button_cancel' class='button' style='color: #000000'>$cancel_button_label</button>";

		$order_id        = $order->get_id();
		$capture_message = __( 'Are you sure you want to capture the payment?' );
		$cancel_message  = __( 'Are you sure you want to cancel the payment?' );

		$get_param      = self::GET_PARAM;
		$capture_action = self::CAPTURE_ACTION;
		$cancel_action  = self::CANCEL_ACTION;

		echo "
            <script type='text/javascript'>
                window.addEventListener('load', function () {
                    const postForm = jQuery(\"form[id='post']\");
                    
                    jQuery('#ul_button_capture').on('click', function () {
                        if (window.confirm('$capture_message')) {
                            postForm.attr('action', 'post.php?post=$order_id&action=edit&$get_param=$capture_action');
                            postForm.submit();
                        }
                    });
            
                    jQuery('#ul_button_cancel').on('click', function () {
                        if (window.confirm('$cancel_message')) {
                            postForm.attr('action', 'post.php?post=$order_id&action=edit&$get_param=$cancel_action');
                            postForm.submit();
                        }
                    });
                });
            </script>";
	}

	public function do_payment_action() {
		if ( ! is_admin() || ! isset( $_GET[ self::GET_PARAM ] ) || ! isset( $_GET['post'] ) ) {
			return;
		}

		$action   = $_GET[ self::GET_PARAM ];
		$order_id = $_GET['post'];
		$order    = wc_get_order( $order_id );

		$gateway = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME );
		if ( WC_Unlimint_Constants::BANKCARD_GATEWAY !== $gateway ) {
			return;
		}

		switch ( $action ) {
			case self::CAPTURE_ACTION:
				$is_api_transaction_updated = $this->update_api_transaction_status( $order, self::COMPLETE_STATUS_TO );
				if ( $is_api_transaction_updated ) {
					$order->set_status( WC_Unlimint_Admin_Order_Status_Fields::PROCESSING_WC );
					$order->save();

					$this->logger->info( __FUNCTION__, "New status 'Processing' has been set for order #$order_id" );
				}
				break;

			case self::CANCEL_ACTION:
				$is_api_transaction_updated = $this->update_api_transaction_status( $order, self::REVERSE_STATUS_TO );
				if ( $is_api_transaction_updated ) {
					$order->set_status( WC_Unlimint_Admin_Order_Status_Fields::CANCELLED_WC );
					$order->save();

					$this->logger->info( __FUNCTION__, "New status 'Cancelled' has been set for order #$order_id" );
				}
				break;

			default:
				$this->logger->error( __FUNCTION__, "Invalid auth payment action: '$action' for order #$order_id" );
				break;
		}
	}

	private function update_api_transaction_status( $order, $status_to ) {
		$order_id           = $order->get_id();
		$amount             = $order->get_total();
		$payment_type_field = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_PAYMENT_TYPE_FIELDNAME );
		$payment_id         = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_PAYMENT_ID_FIELDNAME );

		switch ( $payment_type_field ) {
			case WC_Unlimint_Constants::PAYMENT_TYPE_PAYMENT:
				$api_structure = 'payment_data';
				$api_request   = $this->get_api_request_for_update( $api_structure, $status_to, $amount );
				$endpoint      = "/payments/$payment_id";
				break;

			case WC_Unlimint_Constants::PAYMENT_TYPE_RECURRING:
				$api_structure = 'recurring_data';
				$api_request   = $this->get_api_request_for_update( $api_structure, $status_to, $amount );
				$endpoint      = "/installments/$payment_id";
				break;

			default:
				$this->logger->error( __FUNCTION__, "Invalid payment type: '$payment_type_field' for order #$order_id" );

				return false;
		}

		$api_response = $this->unlimint_sdk->patch( $endpoint, wp_json_encode( $api_request ) );
		if ( ! is_array( $api_response )
		     || empty( $api_response )
		     || (int) $api_response['status'] !== 200
		     || ! isset( $api_response['response'][ $api_structure ]['status'] ) ) {
			$this->logger->error( __FUNCTION__, "Unable to update Unlimint transaction '$payment_id' for order #$order_id" );

			return false;
		}

		return $this->is_payment_status_updated( $payment_type_field, $api_response['response'][ $api_structure ], $status_to );
	}

	/**
	 * @param string $payment_type_field
	 * @param array $api_structure
	 * @param string $status_to
	 *
	 * @return bool
	 */
	private function is_payment_status_updated( $payment_type_field, $api_structure, $status_to ) {
		$status = isset( $api_structure['status'] ) ? $api_structure['status'] : '';

		$is_payment_status_updated = false;

		switch ( $status_to ) {
			case self::COMPLETE_STATUS_TO:
				if ( WC_Unlimint_Constants::PAYMENT_TYPE_PAYMENT === $payment_type_field && WC_Unlimint_Constants::TRANSACTION_STATUS_COMPLETED === $status ) {
					$is_payment_status_updated = true;
				} else if ( WC_Unlimint_Constants::PAYMENT_TYPE_RECURRING === $payment_type_field ) {
					$is_payment_status_updated = isset( $api_structure['is_executed'] ) && ( 'true' === $api_structure['is_executed'] );
				}
				break;

			case self::REVERSE_STATUS_TO:
				$is_payment_status_updated = ( WC_Unlimint_Constants::TRANSACTION_STATUS_VOIDED === $status );
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