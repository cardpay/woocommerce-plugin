<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Credentials {

	public const TYPE_ACCESS_CLIENT = 'client';

	/**
	 * @var mixed|null
	 */
	public $payment;

	/**
	 * @var string
	 */
	public $public_key;

	/**
	 * @var string
	 */
	public $access_token;

	/**
	 * @var string
	 */
	public $terminal_code;

	/**
	 * @var string
	 */
	public $terminal_password;

	/**
	 * @var WC_Unlimit_Logger
	 */
	public $logger;

	public function __construct( $payment_gateway_id = null ) {
		if ( empty( $payment_gateway_id ) ) {
			return;
		}

		$this->logger = new WC_Unlimit_Logger();

		$option_name_prefix = WC_Unlimit_Sdk::get_option_name_prefix( $payment_gateway_id );

		$this->terminal_code     = get_option( $option_name_prefix . 'terminal_code', '' );
		$this->terminal_password = get_option( $option_name_prefix . 'terminal_password', '' );
	}

	/**
	 * @return string
	 */
	public function get_credentials_type() {
		return self::TYPE_ACCESS_CLIENT;
	}

	/**
	 * @return string
	 */
	public function get_terminal_code() {
		return $this->terminal_code;
	}

	/**
	 * @return string
	 */
	public function get_terminal_password() {
		return $this->terminal_password;
	}
}
