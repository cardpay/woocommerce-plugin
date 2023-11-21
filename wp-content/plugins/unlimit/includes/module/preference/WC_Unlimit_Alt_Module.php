<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Alt_Module extends WC_Unlimit_Module_Abstract {

	/**
	 * @var string
	 */
	private $payment_method;

	/**
	 * @var string
	 */
	private $post_structure;

	/**
	 * @param  WC_Unlimit_Gateway_Abstract  $payment  Payment
	 * @param  Order  $order
	 * @param  array  $post_fields
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields, $payment_method, $gateway_postfix ) {
		parent::__construct( $payment, $order, $post_fields );

		$this->payment_method = $payment_method;
		$this->post_structure = 'unlimit_' . $gateway_postfix;

		if ( in_array( $payment_method, [ 'BOLETO', 'PIX' ] ) &&
		     ! isset( $this->post_fields[ $this->post_structure ]['cpf'] ) ) {
			$error_message = 'CPF is not provided for boleto or pix payment';
			$this->logger->error( __FUNCTION__, $error_message );
			throw new WC_Unlimit_Exception( $error_message );
		}

		$api_request = $this->get_common_api_request();

		$api_request['payment_method']                                 = $this->payment_method;
		$api_request[ WC_Unlimit_Constants::PAYMENT_DATA ]['amount']   = $this->order->get_total();
		$api_request[ WC_Unlimit_Constants::PAYMENT_DATA ]['currency'] = get_woocommerce_currency();

		$api_request['customer']['full_name'] = $this->order->get_customer_first_name() .
		                                        ' ' . $this->order->get_customer_last_name();

		$this->api_request = $api_request;
	}
}
