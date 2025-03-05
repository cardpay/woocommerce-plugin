<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Admin_Fields {
	public const FIELD_API_ACCESS_MODE = 'payment_page';
	public const FIELD_TERMINAL_CODE = 'terminal_code';
	public const FIELD_TERMINAL_PASSWORD = 'terminal_password';
	public const FIELD_CALLBACK_SECRET = 'callback_secret';
	public const FIELD_TEST_ENVIRONMENT = 'test_environment';
	public const FIELD_PAYMENT_TITLE = 'payment_title';
	public const FIELD_LOG_TO_FILE = 'log_to_file';

	/**
	 * Field title
	 *
	 * @return array
	 */
	public function field_title( $title = null ) {
		return [
			'title' => __( $title, 'unlimit' ),
			'type'  => 'title',
			'class' => 'ul_title_header',
		];
	}

	/**
	 * @return array
	 */
	public function field_terminal_code() {
		$title = __( 'Terminal code', 'unlimit' );
		return [
			'title'       => $title,
			'type'        => 'text',
			'description' => __( "If \"API access mode\" setting is changed - then \"Terminal code\" value need to be checked and changed (if needed).", 'unlimit' ), //NOSONAR
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	/**
	 * @return array
	 */
	public function field_terminal_password() {
		$title = __( 'Terminal password', 'unlimit' );
		return [
			'title'       => $title,
			'type'        => 'password',
			'description' => __( 'Get your credentials, visit the',
					'unlimit' ) . ' ' .
			                 '<a href="https://unlimit.com" target=_blank>unlimit.com</a>.' . ' ' .
			                 __( "If \"API access mode\" setting is changed - then \"Terminal password\" value need to be checked and changed (if needed).", 'unlimit' ), //NOSONAR
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	public function field_callback_secret() {
		$title = __( 'Callback secret', 'unlimit' );
		return [
			'title'       => $title,
			'type'        => 'password',
			'description' =>
				__( "If \"API access mode\" setting is changed - then \"Callback secret\" value need to be checked and changed (if needed).", 'unlimit' ), //NOSONAR
			'default'     => '',
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	/**
	 * @return array
	 */
	public function field_test_environment() {
		return [
			'title'       => __( 'Test environment', 'unlimit' ),
			'type'        => 'select',
			'description' => __( 'In test environment, the data is sent to the sandbox only.', 'unlimit' ) . ' ' .
			                 __( 'Test and prod credentials (Terminal code, Terminal password, Callback secret) are different',
				                 'unlimit' ),
			'default'     => 'yes',
			'options'     => [
				'no'  => __( 'No', 'unlimit' ),
				'yes' => __( 'Yes', 'unlimit' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $default = null ) {
		$title = __( 'Payment title', 'unlimit' );
		return [
			'title'   => $title,
			'type'    => 'text',
			'default' => __( $default, 'unlimit' ),
			'custom_attributes' => $this->get_custom_attributes($title)
		];
	}

	/**
	 * @return array
	 */
	public function field_log_to_file() {
		return [
			'title'       => __( 'Log to file', 'unlimit' ),
			'type'        => 'select',
			'description' => __( 'Plugin communication log entries will be written to your web server log.', 'unlimit' ),
			'default'     => 'yes',
			'options'     => [
				'false' => __( 'No', 'unlimit' ),
				'true'  => __( 'Yes', 'unlimit' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_api_access_mode() {
		return [
			'title'       => __( 'API access mode', 'unlimit' ),
			'type'        => 'select',
			'description' => __( "If \"Payment page\" mode is selected - payment page by Unlimit", 'unlimit' ) . ' ' .
			                 __( "in iFrame is used for customer data collecting.", 'unlimit' )
			                 . ' < br>'
			                 . __( "If \"Gateway\" mode is selected - embedded payment form in plugin", 'unlimit' ) . ' ' .
			                 __( "is used for customer data collecting.", 'unlimit' ),
			'default'     => 'Payment page',
			'options'     => [
				'payment_page' => __( 'Payment page', 'unlimit' ),
				'gateway'      => __( 'Gateway', 'unlimit' ),
			],
			'onkeyup'     => "formatUlCardField(this.id);",
		];
	}

	/**
	 * @return array
	 */
	public function field_payment_mode() {
		return [
			'title'       => __( 'Payment mode', 'unlimit' ),
			'type'        => 'select',
			'description' => __(
                "Embedded Mode: A pop-up window will open on top, displaying the payment page.",
                'unlimit'
            ) . '<br>' .
			__(
                "Redirect Mode: The current window will fully reload with the payment form URL.",
                'unlimit'
            ),
			'default'     => 'Embedded',
			'options'     => [
				'embedded' => __( 'Embedded', 'unlimit' ),
				'redirect'      => __( 'Redirect', 'unlimit' ),
			]
		];
	}

	public function get_custom_attributes( $title ) {
		return [
			'data-empty-error' => sprintf(__('Empty %s', 'unlimit'), $title),
			'data-invalid-error' => sprintf(__('Invalid %s', 'unlimit'), $title)
		];
	}
}
