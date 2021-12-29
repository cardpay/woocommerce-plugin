<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-admin-fields.php';
require_once __DIR__ . '/interface-wc-unlimint-admin-formfields.php';

class WC_Unlimint_Admin_Boleto_Fields extends WC_Unlimint_Admin_Fields implements WC_Unlimint_Admin_FormFields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_boleto_';

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$form_fields                                                                               = [];
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_CODE ]     = $this->field_terminal_code();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_PASSWORD ] = $this->field_terminal_password();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET ]   = $this->field_callback_secret();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  = $this->field_test_environment();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE ]     = $this->field_payment_title();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_LOG_TO_FILE ]       = $this->field_log_to_file();

		return $form_fields;
	}

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( 'Boleto - Unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( 'Boleto - Unlimint' );
	}
}
