<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-admin-fields.php';

/**
 * Alternative payment methods form fields
 */
class WC_Unlimint_Alt_Admin_Fields extends WC_Unlimint_Admin_Fields {

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$fieldname_prefix = $this->get_fieldname_prefix();

		$form_fields                                                                          = [];
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_CODE ]     = $this->field_terminal_code();
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_PASSWORD ] = $this->field_terminal_password();
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET ]   = $this->field_callback_secret();
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  = $this->field_test_environment();
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE ]     = $this->field_payment_title();
		$form_fields[ $fieldname_prefix . WC_Unlimint_Admin_Fields::FIELD_LOG_TO_FILE ]       = $this->field_log_to_file();

		return $form_fields;
	}
}