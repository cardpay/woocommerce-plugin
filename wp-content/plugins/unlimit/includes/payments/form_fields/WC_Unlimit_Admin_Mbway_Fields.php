<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Admin_Fields.php';

class WC_Unlimit_Admin_Mbway_Fields extends WC_Unlimit_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_mbway_';
	public const MBWAY_TITLE = 'MB WAY - Unlimit';

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( self::MBWAY_TITLE );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( self::MBWAY_TITLE );
	}

	public function get_fieldname_prefix() {
		return self::FIELDNAME_PREFIX;
	}

	public function get_form_fields( $add_accessmode = true ) {
		return parent::get_form_fields( $add_accessmode );
	}
}
