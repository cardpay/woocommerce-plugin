<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/config/WC_Unlimit_Constants.php';

class WC_Unlimit_Configs {

	const ACCESS_TOKEN_PROD = '_ul_access_token_prod';
	const PUBLIC_KEY_PROD = '_ul_public_key_prod';
	const PUBLIC_KEY = '_ul_public_key';
	const ACCESS_TOKEN = '_ul_access_token';

	/**
	 * @throws WC_Unlimit_Exception Load configs exception.
	 */
	public function __construct() {
		$this->update_token_new_version();
		$this->show_notices();
	}

	/**
	 * Show notices in admin
	 */
	private function show_notices() {
		add_action( 'admin_notices', [ $this, 'plugin_review' ] );

		if ( empty( get_option( self::PUBLIC_KEY_PROD ) ) && empty( get_option( self::ACCESS_TOKEN_PROD ) )
		     && ! empty( get_option( '_ul_client_id' ) ) && ! empty( get_option( '_ul_client_secret' ) ) ) {
			add_action( 'admin_notices', [ $this, 'notice_update_access_token' ] );
		}

		if ( empty( $_SERVER['HTTPS'] ) || 'off' === $_SERVER['HTTPS'] ) {
			add_action( 'admin_notices', [ $this, 'notice_https' ] );
		}
	}

	/**
	 * @throws WC_Unlimit_Exception Update token new version exception.
	 */
	private function update_token_new_version() {
		if ( ( empty( get_option( self::PUBLIC_KEY_PROD, '' ) ) || empty( get_option( self::ACCESS_TOKEN_PROD, '' ) ) )
		     && ! empty( get_option( self::PUBLIC_KEY ) ) && ! empty( get_option( self::ACCESS_TOKEN ) ) ) {
			$this->update_token();
		}
	}

	/**
	 * Notice Terminal Password
	 */
	public function notice_update_access_token() {
		$type    = 'error';
		$message = __(
			'Update your credentials with the Terminal password and Terminal code, ' .
			'you need them to continue receiving payments!',
			'unlimit'
		);

		WC_Unlimit_Notices::get_alert_frame( $message, $type );
	}

	public function notice_https() {
		$type    = 'notice-warning';
		$message = __( 'The store should have HTTPS in order to use Unlimit payment methods.', 'unlimit' );

		WC_Unlimit_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * @return false
	 */
	public function plugin_review() {
		return false;
	}

	/**
	 * @throws WC_Unlimit_Exception
	 */
	private function update_token() {
		$this->log->write_log( __FUNCTION__, 'update_token: ' );

		$sdk = WC_Unlimit_Module::get_sdk_instance_singleton();

		if ( $sdk ) {
			update_option( '_ul_public_key_test', get_option( self::PUBLIC_KEY ), true );
			update_option( '_ul_access_token_test', get_option( self::ACCESS_TOKEN ), true );
			update_option( 'checkout_credential_prod', 'no', true );

			update_option( self::PUBLIC_KEY_PROD, get_option( self::PUBLIC_KEY ), true );
			update_option( self::ACCESS_TOKEN_PROD, get_option( self::ACCESS_TOKEN ), true );
			if ( ! empty( get_option( self::PUBLIC_KEY_PROD, '' ) ) && ! empty( get_option( self::ACCESS_TOKEN_PROD, '' ) ) ) {
				update_option( self::PUBLIC_KEY, '' );
				update_option( self::ACCESS_TOKEN, '' );
			}
			update_option( 'checkout_credential_prod', 'yes', true );
		}
	}

	/**
	 * @param array|null $methods Methods.
	 *
	 * @return array
	 */
	public function set_payment_gateway( $methods = null ) {
		$methods[] = WC_Unlimit_Constants::BANKCARD_GATEWAY;
		$methods[] = WC_Unlimit_Constants::APAY_GATEWAY;
		$methods[] = WC_Unlimit_Constants::BOLETO_GATEWAY;
		$methods[] = WC_Unlimit_Constants::GPAY_GATEWAY;
		$methods[] = WC_Unlimit_Constants::MBWAY_GATEWAY;
		$methods[] = WC_Unlimit_Constants::MULTIBANCO_GATEWAY;
		$methods[] = WC_Unlimit_Constants::PAYPAL_GATEWAY;
		$methods[] = WC_Unlimit_Constants::PIX_GATEWAY;
		$methods[] = WC_Unlimit_Constants::SEPA_GATEWAY;
		$methods[] = WC_Unlimit_Constants::SPEI_GATEWAY;

		return $methods;
	}
}
