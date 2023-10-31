<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Logger {

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
		$logger_enabled = $this->logger_enabled();
		if ( $logger_enabled === 'true' ) {
			$this->log( $function, $message, self::INFO_LOG_LEVEL );
		}
	}

	public function logger_enabled() {
		$logger_enabled = false;
		if ( empty( $_REQUEST['payment_method'] ) ) {
			return false;
		}

		switch ( $_REQUEST['payment_method'] ) {
			case 'woo-unlimit-custom':
				$logger_enabled = get_option( 'woocommerce_unlimit_bankcard_log_to_file' );
				break;
			case 'woo-unlimit-ticket':
				$logger_enabled = get_option( 'woocommerce_unlimit_boleto_log_to_file' );
				break;
			case 'woo-unlimit-pix':
				$logger_enabled = get_option( 'woocommerce_unlimit_pix_log_to_file' );
				break;
			case 'woo-unlimit-paypal':
				$logger_enabled = get_option( 'woocommerce_unlimit_paypal_log_to_file' );
				break;
			case 'woo-unlimit-spei':
				$logger_enabled = get_option( 'woocommerce_unlimit_spei_log_to_file' );
				break;
			case 'woo-unlimit-mbway':
				$logger_enabled = get_option( 'woocommerce_unlimit_mbway_log_to_file' );
				break;
			case 'woo-unlimit-sepa':
				$logger_enabled = get_option( 'woocommerce_unlimit_sepa_log_to_file' );
				break;
			case 'woo-unlimit-multibanco':
				$logger_enabled = get_option( 'woocommerce_unlimit_multibanco_log_to_file' );
				break;
			case 'woo-unlimit-gpay':
				$logger_enabled = get_option( 'woocommerce_unlimit_gpay_log_to_file' );
				break;
			default:
				$logger_enabled = false;
				break;
		}

		return $logger_enabled;
	}

	public function error( $function, $message ) {
		$this->log( $function, $message, self::ERROR_LOG_LEVEL );
	}

	public function warning( $function, $message ) {
		$this->log( $function, $message, self::WARNING_LOG_LEVEL );
	}

	private function log( $function, $message, $log_level ) {
		$this->wc_logger->$log_level( '[' . $function . ']: ' . $message );
	}
}