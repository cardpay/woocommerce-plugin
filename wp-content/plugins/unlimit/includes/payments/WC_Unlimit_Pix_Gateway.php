<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Pix_Fields.php';
require_once __DIR__ . '/hooks/WC_Unlimit_Hook_Pix.php';
require_once __DIR__ . '/../module/preference/WC_Unlimit_Module_Pix.php';
require_once __DIR__ . '/WC_Unlimit_Alt_Gateway.php';

/**
 * Unlimit Pix payment method
 */
class WC_Unlimit_Pix_Gateway extends WC_Unlimit_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimit-pix';
	const SHORT_GATEWAY_ID = 'pix';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'Pix',
			new WC_Unlimit_Admin_Pix_Fields(),
			new WC_Unlimit_Hook_Pix( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimit_Module_Pix( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}