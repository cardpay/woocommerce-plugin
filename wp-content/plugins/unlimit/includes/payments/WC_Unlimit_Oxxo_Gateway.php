<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Oxxo_Fields.php';
require_once __DIR__ . '/hooks/WC_Unlimit_Hook_Oxxo.php';
require_once __DIR__ . '/../module/preference/WC_Unlimit_Module_Oxxo.php';
require_once __DIR__ . '/WC_Unlimit_Alt_Gateway.php';

/**
 * Unlimit Oxxo payment method
 */
class WC_Unlimit_Oxxo_Gateway extends WC_Unlimit_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimit-oxxo';
	const SHORT_GATEWAY_ID = 'oxxo';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'OXXO',
			new WC_Unlimit_Admin_Oxxo_Fields(),
			new WC_Unlimit_Hook_Oxxo( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimit_Module_Oxxo( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}

	/**
	 * Process payment
	 *
	 * @param  int  $order_id  Order Id.
	 *
	 * @return array|string[]
	 * @throws WC_Data_Exception
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ($order->get_total() > 99999.99) {
			wc_add_notice(
				'<p>' . __( 'Unable to pay using payment method. Choose another payment method.', 'unlimit' ) . '</p>',
				'error'
			);

			return self::RESPONSE_FOR_FAIL;
		}

		return parent::process_payment($order_id);
	}
}