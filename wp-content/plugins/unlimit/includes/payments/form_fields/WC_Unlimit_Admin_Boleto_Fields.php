<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Admin_Fields.php';

class WC_Unlimit_Admin_Boleto_Fields extends WC_Unlimit_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_boleto_';
	public const BOLETO_TITLE = 'Boleto - Unlimit';

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

	public function field_terminal_code() {
		$result = parent::field_terminal_code();
		unset( $result['description'] );

		return $result;
	}

	public function field_terminal_password() {
		$result                = parent::field_terminal_password();
		$result['description'] =
			__( 'Get your credentials, visit the', 'unlimit' ) . ' ' .
			'<a href="https://unlimit.com" target=_blank>unlimit.com</a>';

		return $result;
	}

	public function field_callback_secret() {
		$result = parent::field_callback_secret();
		unset( $result['description'] );

		return $result;
	}
}