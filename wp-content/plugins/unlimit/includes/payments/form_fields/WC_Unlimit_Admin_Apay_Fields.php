<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Admin_Fields.php';

class WC_Unlimit_Admin_Apay_Fields extends WC_Unlimit_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_apay_';
	public const GOOGLE_TITLE = 'Apple Pay - Unlimit';


	public function get_form_fields( $add_accessmode = false, $settings = [] ) {
		$form_fields                       = parent::get_form_fields( $add_accessmode );
		$form_fields['apple_merchant_id'] = $this->field_apple_merchant_id();
		$form_fields[self::FIELDNAME_PREFIX.'merchant_certificate'] = $this->field_apple_merchant_certificate($settings);
		$form_fields[self::FIELDNAME_PREFIX.'merchant_key'] = $this->field_apple_merchant_key($settings);

		return $form_fields;
	}

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( self::GOOGLE_TITLE );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( self::GOOGLE_TITLE );
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
		$result['description'] = __( 'Get your credentials, visit the',
				'unlimit' ) . ' ' . '<a href="https://unlimit.com" target=_blank>unlimit.com</a>';

		return $result;
	}

	public function field_callback_secret() {
		$result = parent::field_callback_secret();
		unset( $result['description'] );

		return $result;
	}

	public function field_apple_merchant_id() {
		$title = __( 'Apple merchant ID', 'unlimit' );
		return [
			'title'       => $title,
			'type'        => 'password',
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	public function field_apple_merchant_certificate($settings = []) {
		$desc = $settings[self::FIELDNAME_PREFIX.'merchant_certificate'] ?? null;
		$title = __( 'Payment processing certificate', 'unlimit' );
		return [
			'title'       => $title,
			'description' => $desc,
			'type'        => 'file',
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	public function field_apple_merchant_key($settings = []) {
		$desc = $settings[self::FIELDNAME_PREFIX.'merchant_key'] ?? null;
		$title = __( 'Merchant identity certificate', 'unlimit' );
		return [
			'title'       => $title,
			'description' => $desc,
			'type'        => 'file',
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}
}
