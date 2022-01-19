<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-alt-admin-fields.php';

class WC_Unlimint_Admin_Boleto_Fields extends WC_Unlimint_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_boleto_';
	public const BOLETO_TITLE = 'Boleto - Unlimint';

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( self::BOLETO_TITLE );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( self::BOLETO_TITLE );
	}

	public function get_fieldname_prefix() {
		return self::FIELDNAME_PREFIX;
	}
}