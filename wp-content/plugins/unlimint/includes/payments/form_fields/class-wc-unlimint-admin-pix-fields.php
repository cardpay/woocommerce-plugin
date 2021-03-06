<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-alt-admin-fields.php';

class WC_Unlimint_Admin_Pix_Fields extends WC_Unlimint_Alt_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_pix_';
	public const PIX_TITLE = 'Pix - Unlimint';

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( self::PIX_TITLE );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( self::PIX_TITLE );
	}

	public function get_fieldname_prefix() {
		return self::FIELDNAME_PREFIX;
	}

    public function field_terminal_code()
    {
        $result = parent::field_terminal_code();
        unset($result['description']);
        return $result;
    }

    public function field_terminal_password()
    {
        $result = parent::field_terminal_password();
        $result['description'] = __('Get your credentials, visit the <a href="https://unlimint.com" target=_blank>unlimint.com</a>');
        return $result;
    }

    public function field_callback_secret() {
        $result = parent::field_callback_secret();
        unset($result['description']);
        return $result;
    }
}
