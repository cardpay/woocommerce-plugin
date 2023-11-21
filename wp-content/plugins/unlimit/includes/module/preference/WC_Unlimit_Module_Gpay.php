<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Overrides\Order;

require_once __DIR__ . '/WC_Unlimit_Alt_Module.php';

class WC_Unlimit_Module_Gpay extends WC_Unlimit_Alt_Module {

	/**
	 * @param  WC_Unlimit_Gateway_Abstract  $payment  Payment
	 * @param  Order  $order
	 * @param  array  $post_fields
	 *
	 * @throws Exception
	 */
	public function __construct( $payment, $order, $post_fields ) {
		parent::__construct( $payment, $order, $post_fields, 'GOOGLEPAY', 'gpay' );

		if ( ! empty( $_POST["cardpay_custom_gpay"]["signature"] ) ) {
			$this->api_request[ WC_Unlimit_Constants::PAYMENT_DATA ]['encrypted_data'] =
				base64_encode(
					stripcslashes( $_POST["cardpay_custom_gpay"]["signature"] )
				);
		}
	}
}
