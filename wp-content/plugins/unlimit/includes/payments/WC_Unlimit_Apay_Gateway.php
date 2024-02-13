<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Apay_Fields.php';
require_once __DIR__ . '/hooks/WC_Unlimit_Hook_Apay.php';
require_once __DIR__ . '/../module/preference/WC_Unlimit_Module_Apay.php';
require_once __DIR__ . '/WC_Unlimit_Alt_Gateway.php';

/**
 * Unlimit Gpay payment method
 */
class WC_Unlimit_Apay_Gateway extends WC_Unlimit_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimit-apay';
	const SHORT_GATEWAY_ID = 'apay';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'Apple Pay',
			new WC_Unlimit_Admin_Apay_Fields(),
			new WC_Unlimit_Hook_Apay( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimit_Module_Apay( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}