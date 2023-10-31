<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Admin_Fields.php';

class WC_Unlimit_Alt_Admin_Fields extends WC_Unlimit_Admin_Fields {

	/**
	 * @return array
	 */
	public function get_form_fields( $add_access_mode = false ) {
		$field_name_prefix = $this->get_fieldname_prefix();

		$form_fields = [];
		if ( $add_access_mode ) {
			$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_API_ACCESS_MODE ] = $this->field_api_access_mode();
		}

		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_TERMINAL_CODE ]     = $this->field_terminal_code();
		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_TERMINAL_PASSWORD ] =
			$this->field_terminal_password();
		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_CALLBACK_SECRET ]   =
			$this->field_callback_secret();
		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  =
			$this->field_test_environment();
		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_PAYMENT_TITLE ]     = $this->field_payment_title();
		$form_fields[ $field_name_prefix . WC_Unlimit_Admin_Fields::FIELD_LOG_TO_FILE ]       = $this->field_log_to_file();

		return $form_fields;
	}
}