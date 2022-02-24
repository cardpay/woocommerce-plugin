<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

require_once __DIR__ . '/class-wc-unlimint-alt-module.php';

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Module_Ticket extends WC_Unlimint_Alt_Module {

	/**
	 * @param WC_Unlimint_Gateway_Abstract $payment Payment.
	 * @param Order $order
	 * @param array $post_fields
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields ) {
		parent::__construct( $payment, $order, $post_fields, 'BOLETO', 'ticket' );
	}
}
