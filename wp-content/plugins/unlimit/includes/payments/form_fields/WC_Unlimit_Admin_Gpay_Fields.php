<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Admin_Fields.php';

class WC_Unlimit_Admin_Gpay_Fields extends WC_Unlimit_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_gpay_';
	public const GPAY_GOOGLE_MERCHANT_ID = 'google_merchant_id';
	public const GOOGLE_TITLE = 'Google Pay - Unlimit';


	public function get_form_fields( $add_accessmode = false ) {
		$form_fields                       = parent::get_form_fields( $add_accessmode );
		$form_fields['google_merchant_id'] = $this->field_google_merchant_id();

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

	public function field_google_merchant_id() {
		return [
			'title'       => __( 'Google merchant ID', 'unlimit' ),
			'type'        => 'password',
			'description' => __( 'Your Merchant ID, provided by Google.', 'unlimit' ),
			'default'     => '',
		];
	}
}
