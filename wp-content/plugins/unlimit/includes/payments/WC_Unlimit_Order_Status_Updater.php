<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/log/WC_Unlimit_Logger.php';

class WC_Unlimit_Order_Status_Updater {
	private const PREFIX = 'wc-';

	private $logger;

	public function __construct() {
		$this->logger = new WC_Unlimit_Logger();
	}

	public function update_order_status( $order, $new_status ) {
		if ( empty( $order->get_status() ) ) {
			$this->logger->error( __FUNCTION__, "Status is null. Status: " . $order->get_id() );

			return;
		}

		$new_status_data = $this->get_normalized_status( $order, $new_status );

		$old_status = $new_status_data['from'];
		$new_status_set = $new_status_data['to'];

		$this->logger->log_callback_request( __FUNCTION__,
			"Attempting to update order status for order #" . $order->get_id() . " from $old_status to $new_status_set" );

		if ( $old_status !== $new_status_set ) {
			$this->logger->log_callback_request( __FUNCTION__, "Order status updated to '$new_status_set'." );

			$order->set_status( $new_status_set );
			$order->save();

			$this->logger->log_callback_request(
				__FUNCTION__,
				"Order status was updated, order #" . $order->get_id() .
				", old status: $old_status, new status: $new_status_set"
			);
		} else {
			$this->logger->log_callback_request( __FUNCTION__, "Order status wasn't changed" );
		}
	}

	private function get_normalized_status( $order, $new_status ) {
		$old_status = $order->get_status();
		$new_status = self::PREFIX === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;

		return [
			'from' => $old_status,
			'to'   => $new_status,
		];
	}
}
