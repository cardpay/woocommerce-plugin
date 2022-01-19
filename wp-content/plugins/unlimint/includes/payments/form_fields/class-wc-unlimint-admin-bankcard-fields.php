<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-admin-fields.php';

class WC_Unlimint_Admin_BankCard_Fields extends WC_Unlimint_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_bankcard_';

	public const FIELD_CAPTURE_PAYMENT = 'capture_payment';
	public const FIELD_INSTALLMENT_ENABLED = 'installment_enabled';
	public const FIELD_ASK_CPF = 'ask_cpf';
	public const FIELD_DYNAMIC_DESCRIPTOR = 'dynamic_descriptor';

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$form_fields                                                                               = [];
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_CODE ]     = $this->field_terminal_code();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_PASSWORD ] = $this->field_terminal_password();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET ]   = $this->field_callback_secret();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  = $this->field_test_environment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_CAPTURE_PAYMENT ]                       = $this->field_capture_payment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_ENABLED ]                   = $this->field_installment_enabled();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE ]     = $this->field_payment_title();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_ASK_CPF ]                               = $this->field_ask_cpf();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_DYNAMIC_DESCRIPTOR ]                    = $this->field_dynamic_descriptor();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_LOG_TO_FILE ]       = $this->field_log_to_file();

		return $form_fields;
	}

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( 'Credit Card - Unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_capture_payment() {
		return [
			'title'       => __( 'Capture Payment', 'unlimint' ),
			'type'        => 'select',
			'description' => __( "If set to 'No', the amount will not be captured but only blocked. With 'No' option selected payments will be captured automatically in 7 days from the time of creating the preauthorized transaction. In installment case with 'No' option selected installments will be declined automatically in 7 days from the time of creating the preauthorized transaction.", 'unlimint' ),
			'default'     => 'yes',
			'options'     => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_installment_enabled() {
		return [
			'title'   => __( 'Installment Enabled', 'unlimint' ),
			'type'    => 'select',
			'default' => 'no',
			'options' => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( 'Credit Card - Unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_ask_cpf() {
		return [
			'title'   => __( 'Ask CPF', 'unlimint' ),
			'type'    => 'select',
			'default' => 'no',
			'options' => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_dynamic_descriptor() {
		return [
			'title'   => __( 'Dynamic Descriptor', 'unlimint' ),
			'type'    => 'text',
			'default' => '',
		];
	}
}