<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Sepa_Fields.php';
require_once __DIR__ . '/hooks/WC_Unlimit_Hook_Sepa.php';
require_once __DIR__ . '/../module/preference/WC_Unlimit_Module_Sepa.php';
require_once __DIR__ . '/WC_Unlimit_Alt_Gateway.php';

/**
 * Unlimit Sepa payment method
 */
class WC_Unlimit_Sepa_Gateway extends WC_Unlimit_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimit-sepa';
	const SHORT_GATEWAY_ID = 'sepa';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'SEPA Instant',
			new WC_Unlimit_Admin_Sepa_Fields(),
			new WC_Unlimit_Hook_Sepa( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimit_Module_Sepa( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}