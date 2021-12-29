<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Module_Ticket extends WC_Unlimint_Module_Abstract {

	/**
	 * @param WC_Unlimint_Payment_Abstract $payment Payment.
	 * @param Order $order
	 * @param mixed $ticket_checkout Ticket checkout.
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $ticket_checkout ) {
		parent::__construct( $payment, $order, $ticket_checkout );

		if ( ! isset( $this->post_fields['unlimint_ticket'] ) || ! isset( $this->post_fields['unlimint_ticket']['cpf'] ) ) {
			$error_message = 'cpf is not provided';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimint_Exception( $error_message );
		}

		$this->build_api_request();
	}

	private function build_api_request() {
		$api_request                             = $this->get_common_api_request();

		$api_request['payment_method']           = 'BOLETO';
		$api_request['payment_data']['amount']   = $this->order->get_total();
		$api_request['payment_data']['currency'] = get_woocommerce_currency();
		$api_request['customer']['identity']     = $this->post_fields['unlimint_ticket']['cpf'];

		$api_request['customer']['full_name'] = $this->order->get_customer_first_name() . ' ' . $this->order->get_customer_last_name();

		$this->api_request = $api_request;
	}
}
