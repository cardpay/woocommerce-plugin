<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-boleto-fields.php';
require_once __DIR__ . '/hooks/class-wc-unlimint-hook-ticket.php';
require_once __DIR__ . '/../module/preference/class-wc-unlimint-module-ticket.php';
require_once __DIR__ . '/class-wc-unlimint-alt-gateway.php';

/**
 * Unlimint Boleto payment method
 */
class WC_Unlimint_Ticket_Gateway extends WC_Unlimint_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimint-ticket';
	const SHORT_GATEWAY_ID = 'ticket';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'Boleto',
			new WC_Unlimint_Admin_Boleto_Fields(),
			new WC_Unlimint_Hook_Ticket( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimint_Module_Ticket( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}