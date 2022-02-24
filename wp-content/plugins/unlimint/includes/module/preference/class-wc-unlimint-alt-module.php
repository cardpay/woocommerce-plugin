<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Alt_Module extends WC_Unlimint_Module_Abstract {

	/**
	 * @var string
	 */
	private $payment_method;

	/**
	 * @var string
	 */
	private $post_structure;

	/**
	 * @param WC_Unlimint_Gateway_Abstract $payment Payment
	 * @param Order $order
	 * @param array $post_fields
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields, $payment_method, $gateway_postfix ) {
		parent::__construct( $payment, $order, $post_fields );

		$this->payment_method = $payment_method;
		$this->post_structure = 'unlimint_' . $gateway_postfix;

		if ( ! isset( $this->post_fields[ $this->post_structure ]['cpf'] ) ) {
			$error_message = 'CPF is not provided';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimint_Exception( $error_message );
		}

		$api_request = $this->get_common_api_request();

		$api_request['payment_method']           = $this->payment_method;
		$api_request['payment_data']['amount']   = $this->order->get_total();
		$api_request['payment_data']['currency'] = get_woocommerce_currency();
		$api_request['customer']['identity']     = $this->post_fields[ $this->post_structure ]['cpf'];

		$api_request['customer']['full_name'] = $this->order->get_customer_first_name() . ' ' . $this->order->get_customer_last_name();

		$this->api_request = $api_request;
	}
}