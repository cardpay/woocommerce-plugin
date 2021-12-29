<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Logger {
	private const INFO_LOG_LEVEL = 'info';
	private const ERROR_LOG_LEVEL = 'error';
	private const WARNING_LOG_LEVEL = 'warning';

	/**
	 * @var WC_Logger|null
	 */
	private $wc_logger;

	/**
	 * @var bool
	 */
	private $debug_mode;

	public function __construct( $payment = null ) {
		$this->wc_logger = wc_get_logger();

		$debug_mode = true;
		if ( ! is_null( $payment ) && 'no' === $payment->debug_mode ) {
			$debug_mode = false;
		}

		$this->debug_mode = $debug_mode;
	}

	public function info( $function, $message ) {
		$this->log( $function, $message, self::INFO_LOG_LEVEL );
	}

	public function error( $function, $message ) {
		$this->log( $function, $message, self::ERROR_LOG_LEVEL );
	}

	public function warning( $function, $message ) {
		$this->log( $function, $message, self::WARNING_LOG_LEVEL );
	}

	private function log( $function, $message, $log_level ) {
		if ( ! $this->debug_mode || is_null( $this->wc_logger ) ) {
			return;
		}

		$this->wc_logger->$log_level( '[' . $function . ']: ' . $message );
	}
}