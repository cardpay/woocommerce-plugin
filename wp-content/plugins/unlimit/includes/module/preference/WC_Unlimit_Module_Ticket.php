<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

require_once __DIR__ . '/WC_Unlimit_Alt_Module.php';

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Module_Ticket extends WC_Unlimit_Alt_Module {

	/**
	 * @param WC_Unlimit_Gateway_Abstract $payment Payment.
	 * @param Order $order
	 * @param array $post_fields
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields ) {
		parent::__construct( $payment, $order, $post_fields, 'BOLETO', 'ticket' );
		$this->api_request['customer']['identity'] = $post_fields['unlimit_ticket']['cpf'];
	}
}
