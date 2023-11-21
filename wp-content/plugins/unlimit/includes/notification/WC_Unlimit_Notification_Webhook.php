<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';

class WC_Unlimit_Notification_Webhook extends WC_Unlimit_Notification_Abstract {

	/**
	 * Return Actions
	 */
	public function check_action_response() {
		$data = $_GET;

		if ( isset( $data['action'], $data['order_id'] ) ) {
			$redirect_url = '';
			$order_id     = $data['order_id'];
			$order        = wc_get_order( $order_id );

			switch ( $data['action'] ) {
				case 'cancel' :
				case 'decline' :
					$this->restore_cart( $order );
					$redirect_url = wc_get_checkout_url();
					if ( ! wc_notice_count( 'error' ) ) {
						wc_add_notice( __( 'Order was unsuccessful', 'unlimit' ), 'error' );
					}
					break;

				case 'inprocess' :
					$redirect_url = $order->get_checkout_order_received_url() . '&noredir=1';
					break;

				case 'success' :
					WC()->cart->empty_cart();
					$redirect_url = $order->get_checkout_order_received_url() . '&noredir=1';
					break;

				default:
					break;
			}

			wp_safe_redirect( $redirect_url );

		} else {
			$this->check_in_response();
		}
	}

	private function restore_cart( $order ) {
		WC()->cart->empty_cart();

		foreach ( $order->get_items() as $product ) {
			$product_id  = isset( $product['product_id'] ) ? (int) $product['product_id'] : 0;
			$quantity    = isset( $product['quantity'] ) ? (int) $product['quantity'] : 1;
			$variationId = isset( $product['variation_id'] ) ? (int) $product['variation_id'] : 0;
			$variation   = isset( $product['variation'] ) ? $product['variation'] : [];

			WC()->cart->add_to_cart( $product_id, $quantity, $variationId, $variation );
		}

		WC()->cart->calculate_totals();
	}

	public function check_in_response() {
		$err  = true;
		$data = file_get_contents( "php://input" );

		if ( ! empty( $data ) ) {
			$data = json_decode( $data, true );

			if ( isset( $data['callback_time'] ) && isset( $data[ WC_Unlimit_Constants::PAYMENT_DATA ] ) ) {
				$err = false;
				do_action( 'valid_unlimit_ipn_request', $data );
				$this->set_response( 200, 'OK', 'Notification IPN is successful' );
			}
		}

		if ( $err === true ) {
			$this->logger->error( __FUNCTION__, 'Data: ' . $data );
			$this->logger->error( __FUNCTION__, 'Wrong params in Request IPN.' );
			$this->set_response( 422, null, __( 'Wrong params in Request IPN', 'unlimit' ) );
		}
	}

	/**
	 * @param  array  $data  Payment data
	 */
	public function successful_request( $data ) {
		try {
			$order  = parent::successful_request( $data );
			$status = $this->process_status_ul_business( $data, $order );

			$this->logger->info(
				__FUNCTION__,
				'Changing order status to: ' . $status
			);

			$this->proccess_status( $status, $data, $order );
		} catch ( Exception $e ) {
			$this->logger->error( __FUNCTION__, $e->getMessage() );
		}
	}

	/**
	 * @param  array  $data  Payment data.
	 * @param  object  $order  Order.
	 *
	 * @return mixed|string
	 */
	public function process_status_ul_business( $data, $order ) {
		$status       = $data[ WC_Unlimit_Constants::PAYMENT_DATA ]['status'] ?? 'PENDING';
		$total_paid   = $data[ WC_Unlimit_Constants::PAYMENT_DATA ]['amount'] ?? 0.00;
		$total_refund = $data['refund_data']['amount'] ?? 0.00;

		// Updates the type of gateway.
		WC_Unlimit_Helper::set_order_meta( $order, __( 'Payment type', 'unlimit' ), WC_Unlimit_Constants::PAYMENT_DATA );
		WC_Unlimit_Helper::set_order_meta( $order, WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME,
			get_class( $this ) );

		if ( ! empty( $data['payer']['email'] ) ) {
			WC_Unlimit_Helper::set_order_meta( $order, __( 'Buyer email', 'unlimit' ), $data['payer']['email'] );
		}
		if ( ! empty( $data['payment_method_id'] ) ) {
			WC_Unlimit_Helper::set_order_meta( $order, __( 'Payment method', 'unlimit' ), $data['payment_method_id'] );
		}

		WC_Unlimit_Helper::set_order_meta( $order,
			'Unlimit - Payment ' . $data['id'],
			'[Date ' . gmdate( 'Y-m-d H:i:s', strtotime( $data['date_created'] ) ) .
			']/[Status ' . $status .
			']/[Paid ' . $total_paid .
			']/[Amount ' . $data['transaction_amount'] .
			']/[Paid ' . $total_paid .
			']/[Refund ' . $total_refund . ']'
		);

		WC_Unlimit_Helper::set_order_meta( $order, '_Unlimit_Payment_IDs', $data['id'] );
		$order->save();

		return $status;
	}
}
