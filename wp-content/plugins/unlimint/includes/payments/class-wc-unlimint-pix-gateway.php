<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-pix-fields.php';
require_once __DIR__ . '/hooks/class-wc-unlimint-hook-pix.php';
require_once __DIR__ . '/../module/preference/class-wc-unlimint-module-pix.php';
require_once __DIR__ . '/class-wc-unlimint-alt-gateway.php';

/**
 * Unlimint Pix payment method
 */
class WC_Unlimint_Pix_Gateway extends WC_Unlimint_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimint-pix';
	const SHORT_GATEWAY_ID = 'pix';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'Pix',
			new WC_Unlimint_Admin_Pix_Fields(),
			new WC_Unlimint_Hook_Pix( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimint_Module_Pix( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}