<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/rest-client/class-rest-client-abstract.php';
require_once __DIR__ . '/rest-client/class-unlimint-rest-client.php';
require_once __DIR__ . '/../../../payments/form_fields/class-wc-unlimint-admin-bankcard-fields.php';
require_once __DIR__ . '/../../../payments/form_fields/class-wc-unlimint-admin-boleto-fields.php';
require_once __DIR__ . '/../../../payments/form_fields/class-wc-unlimint-admin-pix-fields.php';
require_once __DIR__ . '/../../../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/../../../module/class-wc-unlimint-helper.php';

$GLOBALS['LIB_LOCATION'] = __DIR__;

class Unlimint_Sdk {
	private const BEARER = 'Bearer ';

	/**
	 * @var false|mixed
	 */
	private $terminal_code;

	/**
	 * @var false|mixed
	 */
	private $terminal_password;

	/**
	 * @var bool
	 */
	private $is_sandbox;

	/**
	 * @var string
	 */
	private $access_token;

	/**
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	/**
	 * @throws WC_Unlimint_Exception UnlimintSdk Class exception.
	 */
	public function __construct( $payment_gateway_id ) {
		$credentials             = new WC_Unlimint_Credentials( $payment_gateway_id );
		$this->terminal_code     = $credentials->get_terminal_code();
		$this->terminal_password = $credentials->get_terminal_password();

		$option_name_prefix = Unlimint_Sdk::get_option_name_prefix( $payment_gateway_id );
		$test_env_option    = get_option( $option_name_prefix . 'test_environment', '' );
		$this->is_sandbox   = ( 'yes' === $test_env_option );

		$this->logger = new WC_Unlimint_Logger();
	}

	public function getApiUrlPrefix() {
		$urlPrefix = WC_Unlimint_Constants::API_UL_BASE_URL;

		if ( $this->is_sandbox ) {
			$urlPrefix = WC_Unlimint_Constants::API_UL_SANDBOX_URL;
		}

		return $urlPrefix;
	}

	/**
	 * Get Terminal Password
	 *
	 * @return mixed|null
	 */
	public function get_access_token() {
		if ( empty( $this->terminal_code ) || empty( $this->terminal_password ) ) {
			return null;
		}

		$api_request_params = [
			'terminal_code' => $this->terminal_code,
			'password'      => $this->terminal_password,
			'grant_type'    => 'password',
			'sandbox'       => $this->is_sandbox
		];

		$response_data = Unlimint_Rest_Client::post(
			[
				'uri'     => '/auth/token',
				'data'    => $api_request_params,
				'headers' => [
					'content-type' => 'application/x-www-form-urlencoded',
				],
			],
			$this->getApiUrlPrefix()
		);

		$response = $response_data['response'];
		if ( 'bearer' !== $response['token_type'] ) {
			return null;
		}

		$this->access_token = $response['access_token'];

		return $this->access_token;
	}

	/**
	 * @param array $preference Preference data.
	 *
	 * @return array|null
	 */
	public function create_preference( $preference ) {
		$request = [
			'uri'     => '/checkout/preferences',
			'headers' => [
				'user-agent'    => 'platform:desktop,type:woocommerce,so:' . WC_Unlimint_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return Unlimint_Rest_Client::post( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param string $id Preference id.
	 *
	 * @return array|null
	 */
	public function get_preference( $id ) {

		$request = [
			'headers' => [
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'uri'     => "/checkout/preferences/$id",
		];

		return Unlimint_Rest_Client::get( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param array $preference Preference.
	 *
	 * @return array|null
	 */
	public function create_payment( $preference ) {

		$request = [
			'uri'     => '/payments',
			'headers' => [
				'X-Tracking-Id' => 'platform:woocommerce,so:' . WC_Unlimint_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return Unlimint_Rest_Client::post( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param $preference
	 *
	 * @return array|null
	 */
	public function refund_payment( $preference ) {
		$request = [
			'uri'     => '/refunds',
			'headers' => [
				'X-Tracking-Id' => 'platform:woocommerce,so:' . WC_Unlimint_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return Unlimint_Rest_Client::post( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param string $id Payment id.
	 *
	 * @return array|null
	 */
	public function cancel_payment( $id ) {

		$request = [
			'headers' => [
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'uri'     => '/payments/' . $id,
			'data'    => '{"status":"cancelled"}',
		];

		return Unlimint_Rest_Client::put( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param string $access_token Terminal Password.
	 *
	 * @return array|null
	 */
	public function get_payment_methods( $access_token ) {
		$request = [
			'headers' => [
				'Authorization' => self::BEARER . $access_token,
			],
			'uri'     => '/payment_methods',
		];

		$response = Unlimint_Rest_Client::get( $request, $this->getApiUrlPrefix() );

		if ( (int) $response['status'] > 202 ) {
			$this->logger->error( __FUNCTION__, $response['response']['message'] );

			return null;
		}

		asort( $response );

		return $response;
	}

	/**
	 * @param string $uri
	 * @param array $params
	 * @param array $headers Headers.
	 * @param bool $authenticate Is authenticate.
	 *
	 * @return array|null
	 */
	public function get( $uri, $params = [], $headers = [], $authenticate = true ) {
		$request = [
			'headers'      => $headers,
			'uri'          => $uri,
			'authenticate' => $authenticate,
			'params'       => $params
		];

		$get_access_token = $this->get_access_token();

		if ( empty( $get_access_token ) ) {
			$this->logger->error( __FUNCTION__, 'Empty access token' );

			return null;
		}

		$request['headers'] = [ 'Authorization' => self::BEARER . $get_access_token ];

		return Unlimint_Rest_Client::get( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param string $uri
	 * @param null $data Request data.
	 * @param null $params Request params.
	 *
	 * @return array|null
	 */
	public function post( $uri, $data = null, $params = null ) {
		$request = [
			'headers' => [ 'Authorization' => self::BEARER . $this->get_access_token() ],
			'uri'     => $uri,
			'data'    => $data,
			'params'  => $params,
		];

		$request['params'] = isset( $request['params'] ) && is_array( $request['params'] ) ? $request['params'] : [];

		$this->log_request( $data );

		return Unlimint_Rest_Client::post( $request, $this->getApiUrlPrefix() );
	}

	public function patch( $uri, $data = null, $params = null ) {
		$request = [
			'headers' => [ 'Authorization' => self::BEARER . $this->get_access_token() ],
			'uri'     => $uri,
			'data'    => $data,
			'params'  => $params,
		];

		$request['params'] = isset( $request['params'] ) && is_array( $request['params'] ) ? $request['params'] : [];

		$this->log_request( $data );

		return Unlimint_Rest_Client::patch( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param array|string $request Request.
	 * @param null $data Request data.
	 * @param null $params Request params.
	 *
	 * @return array|null
	 */
	public function put( $uri, $data = null, $params = null ) {
		$request = [
			'headers' => [ 'Authorization' => self::BEARER . $this->get_access_token() ],
			'uri'     => $uri,
			'data'    => $data,
			'params'  => $params,
		];

		return Unlimint_Rest_Client::put( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @param array $request Request.
	 * @param null|array $params Params.
	 *
	 * @return array|null
	 */
	public function delete( $uri, $params = null ) {
		$request = [
			'headers' => [ 'Authorization' => self::BEARER . $this->get_access_token() ],
			'uri'     => $uri,
			'params'  => $params,
		];

		return Unlimint_Rest_Client::delete( $request, $this->getApiUrlPrefix() );
	}

	/**
	 * @throws WC_Unlimint_Exception
	 */
	public static function get_option_name_prefix( $payment_gateway_id ) {
		switch ( $payment_gateway_id ) {
			case WC_Unlimint_Custom_Gateway::GATEWAY_ID:
				return WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX;

			case WC_Unlimint_Ticket_Gateway::GATEWAY_ID:
				return WC_Unlimint_Admin_Boleto_Fields::FIELDNAME_PREFIX;

			case WC_Unlimint_Pix_Gateway::GATEWAY_ID:
				return WC_Unlimint_Admin_Pix_Fields::FIELDNAME_PREFIX;

			default:
				throw new WC_Unlimint_Exception( 'Invalid payment gateway id provided' );
		}
	}

	/**
	 * @param string $email E-mail.
	 */
	public function set_email( $email ) {
		Unlimint_Rest_Client::set_email( $email );
	}

	/**
	 * @param string $country_code Country code.
	 */
	public function set_locale( $country_code ) {
		Unlimint_Rest_Client::set_locale( $country_code );
	}

	/**
	 * @param $request
	 */
	private function log_request( $request ) {
		$data_decoded = json_decode( $request );

		if ( ! empty( $data_decoded ) && ! empty( $data_decoded->card_account ) && ! empty( $data_decoded->card_account->card ) ) {
			$data_decoded->card_account->card->pan           = WC_Unlimint_Helper::mask_card_pan( $data_decoded->card_account->card->pan );
			$data_decoded->card_account->card->security_code = WC_Unlimint_Constants::SECURITY_CODE_MASKED;
		}

		$this->logger->info( __FUNCTION__, 'Request: ' . wp_json_encode( $data_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}
}