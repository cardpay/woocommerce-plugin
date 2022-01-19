<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Module_Pix extends WC_Unlimint_Module_Abstract {

	/**
	 * @param WC_Unlimint_Gateway_Abstract $payment Payment
	 * @param Order $order
	 * @param mixed $pix_checkout Pix checkout
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $pix_checkout ) {
		parent::__construct( $payment, $order, $pix_checkout );

		if ( ! isset( $this->post_fields['unlimint_pix'] ) || ! isset( $this->post_fields['unlimint_pix']['cpf'] ) ) {
			$error_message = 'CPF is not provided';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimint_Exception( $error_message );
		}

		$this->build_api_request();
	}

	private function build_api_request() {
		$api_request = $this->get_common_api_request();

		$api_request['payment_method']           = 'PIX';
		$api_request['payment_data']['amount']   = $this->order->get_total();
		$api_request['payment_data']['currency'] = get_woocommerce_currency();
		$api_request['customer']['identity']     = $this->post_fields['unlimint_pix']['cpf'];

		$api_request['customer']['full_name'] = $this->order->get_customer_first_name() . ' ' . $this->order->get_customer_last_name();

		$this->api_request = $api_request;
	}
}