<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Gpay_Fields.php';
require_once __DIR__ . '/hooks/WC_Unlimit_Hook_Gpay.php';
require_once __DIR__ . '/../module/preference/WC_Unlimit_Module_Gpay.php';
require_once __DIR__ . '/WC_Unlimit_Alt_Gateway.php';

/**
 * Unlimit Gpay payment method
 */
class WC_Unlimit_Gpay_Gateway extends WC_Unlimit_Alt_Gateway {

	const GATEWAY_ID = 'woo-unlimit-gpay';
	const SHORT_GATEWAY_ID = 'gpay';

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct(
			self::GATEWAY_ID,
			self::SHORT_GATEWAY_ID,
			'Google Pay',
			new WC_Unlimit_Admin_Gpay_Fields(),
			new WC_Unlimit_Hook_Gpay( $this )
		);
	}

	/**
	 * @throws Exception
	 */
	public function get_module( $order, $post_fields ) {
		return new WC_Unlimit_Module_Gpay( $this, $order, $post_fields );
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}

	public function process_payment( $order_id ) {
		$this->logger->info( __FUNCTION__,
			'Alternative payment method, POST data: ' . wp_json_encode( $_POST,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);

		$order = wc_get_order( $order_id );

		$api_request  = $this->get_module( $order, $_POST )->get_api_request();
		$api_response = $this->call_api( $api_request );

		return $this->handle_api_response( $api_response, $order );
	}
}