<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Admin_Fields {
	public const FIELD_TITLE = 'title';
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
			'title' => __( $title, 'unlimint' ),
			'type'  => 'title',
			'class' => 'ul_title_header',
		];
	}

	/**
	 * @return array
	 */
	public function field_terminal_code() {
		return [
			'title'   => __( 'Terminal Code', 'unlimint' ),
			'type'    => 'text',
			'default' => '',
		];
	}

	/**
	 * @return array
	 */
	public function field_terminal_password() {
		return [
			'title'       => __( 'Terminal Password', 'unlimint' ),
			'type'        => 'password',
			'description' => __( 'Get your credentials, visit the unlimint.com', 'unlimint' ),
			'default'     => '',
		];
	}

	public function field_callback_secret() {
		return [
			'title'   => __( 'Callback Secret', 'unlimint' ),
			'type'    => 'password',
			'default' => '',
		];
	}

	/**
	 * @return array
	 */
	public function field_test_environment() {
		return [
			'title'       => __( 'Test Environment', 'unlimint' ),
			'type'        => 'select',
			'description' => __( 'In test mode, the data is sent to the sandbox.', 'unlimint' ),
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
	public function field_payment_title( $title = null ) {
		return [
			'title'   => __( 'Payment Title', 'unlimint' ),
			'type'    => 'text',
			'default' => __( $title, 'unlimint' ),
		];
	}

	/**
	 * @return array
	 */
	public function field_log_to_file() {
		return [
			'title'   => __( 'Log to File', 'unlimint' ),
			'type'    => 'select',
			'default' => 'yes',
			'options' => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}
}