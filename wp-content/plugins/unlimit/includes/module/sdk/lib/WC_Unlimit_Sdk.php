<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/rest-client/WC_Unlimit_Rest_Client_Abstract.php';
require_once __DIR__ . '/rest-client/WC_Unlimit_Rest_Client.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Apay_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_BankCard_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Boleto_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Gpay_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Mbway_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Multibanco_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Paypal_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Pix_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Sepa_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Spei_Fields.php';
require_once __DIR__ . '/../../../payments/form_fields/WC_Unlimit_Admin_Oxxo_Fields.php';
require_once __DIR__ . '/../../../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/../../../module/WC_Unlimit_Helper.php';

$GLOBALS['LIB_LOCATION'] = __DIR__;

class WC_Unlimit_Sdk {
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
	 * @var WC_Unlimit_Logger
	 */
	public $logger;

	/**
	 * @throws WC_Unlimit_Exception WC_Unlimit_Sdk Class exception.
	 */
	public function __construct( $payment_gateway_id ) {
		$credentials             = new WC_Unlimit_Credentials( $payment_gateway_id );
		$this->terminal_code     = $credentials->get_terminal_code();
		$this->terminal_password = $credentials->get_terminal_password();

		$option_name_prefix = WC_Unlimit_Sdk::get_option_name_prefix( $payment_gateway_id );
		$test_env_option    = get_option( $option_name_prefix . 'test_environment', '' );
		$this->is_sandbox   = ( 'yes' === $test_env_option );

		$this->logger = new WC_Unlimit_Logger();
	}

	public function get_api_url_prefix() {
		$urlPrefix = WC_Unlimit_Constants::API_UL_BASE_URL;

		if ( $this->is_sandbox ) {
			$urlPrefix = WC_Unlimit_Constants::API_UL_SANDBOX_URL;
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

		$response_data = WC_Unlimit_Rest_Client::post(
			[
				'uri'     => '/auth/token',
				'data'    => $api_request_params,
				'headers' => [
					'content-type' => 'application/x-www-form-urlencoded',
				],
			],
			$this->get_api_url_prefix()
		);

		$response = $response_data['response'];
		if ( 'bearer' !== $response['token_type'] ) {
			return null;
		}

		$access_token = $response['access_token'];

		return $access_token;
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
				'user-agent'    => 'platform:desktop,type:woocommerce,so:' . WC_Unlimit_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return WC_Unlimit_Rest_Client::post( $request, $this->get_api_url_prefix() );
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

		return WC_Unlimit_Rest_Client::get( $request, $this->get_api_url_prefix() );
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
				'X-Tracking-Id' => 'platform:woocommerce,so:' . WC_Unlimit_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return WC_Unlimit_Rest_Client::post( $request, $this->get_api_url_prefix() );
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
				'X-Tracking-Id' => 'platform:woocommerce,so:' . WC_Unlimit_Constants::VERSION,
				'Authorization' => self::BEARER . $this->get_access_token(),
			],
			'data'    => $preference,
		];

		return WC_Unlimit_Rest_Client::post( $request, $this->get_api_url_prefix() );
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

		return WC_Unlimit_Rest_Client::put( $request, $this->get_api_url_prefix() );
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

		$response = WC_Unlimit_Rest_Client::get( $request, $this->get_api_url_prefix() );

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

		return WC_Unlimit_Rest_Client::get( $request, $this->get_api_url_prefix() );
	}

	/**
	 * @param string $uri
	 * @param null $data Request data.
	 * @param null $params Request params.
	 *
	 * @return array|null
	 */
	public function post( $uri, $data = null, $params = null ) {
		$request = $this->get_request( $uri, $data, $params );

		return WC_Unlimit_Rest_Client::post( $request, $this->get_api_url_prefix() );
	}

	public function patch( $uri, $data = null, $params = null ) {
		$request = $this->get_request( $uri, $data, $params );

		return WC_Unlimit_Rest_Client::patch( $request, $this->get_api_url_prefix() );
	}

	/**
	 * @param $uri
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

		return WC_Unlimit_Rest_Client::put( $request, $this->get_api_url_prefix() );
	}

	/**
	 * @param $uri
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

		return WC_Unlimit_Rest_Client::delete( $request, $this->get_api_url_prefix() );
	}

	/**
	 * @throws WC_Unlimit_Exception
	 */
	public static function get_option_name_prefix( $payment_gateway_id ) {
		switch ( $payment_gateway_id ) {
			case WC_Unlimit_Custom_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Apay_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Apay_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Ticket_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Boleto_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Mbway_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Mbway_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Pix_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Pix_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Paypal_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Paypal_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Spei_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Spei_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Sepa_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Sepa_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Gpay_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Gpay_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Multibanco_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Multibanco_Fields::FIELDNAME_PREFIX;
				break;

			case WC_Unlimit_Oxxo_Gateway::GATEWAY_ID:
				$prefix = WC_Unlimit_Admin_Oxxo_Fields::FIELDNAME_PREFIX;
				break;

			default:
				throw new WC_Unlimit_Exception( 'Invalid payment gateway id provided' );
		}

		return $prefix;
	}

	/**
	 * @param string $email E-mail.
	 */
	public function set_email( $email ) {
		WC_Unlimit_Rest_Client::set_email( $email );
	}

	/**
	 * @param string $country_code Country code.
	 */
	public function set_locale( $country_code ) {
		WC_Unlimit_Rest_Client::set_locale( $country_code );
	}

	/**
	 * @param $request
	 */
	private function log_request( $request ) {
		$data_decoded = json_decode( $request );

		if (
			! empty( $data_decoded ) &&
			! empty( $data_decoded->card_account ) &&
			! empty( $data_decoded->card_account->card )
		) {
			$data_decoded->card_account->card->pan           = WC_Unlimit_Helper::mask_card_pan(
				$data_decoded->card_account->card->pan
			);
			$data_decoded->card_account->card->security_code = WC_Unlimit_Constants::SECURITY_CODE_MASKED;
		}

		$this->logger->info( __FUNCTION__,
			'Request: ' . wp_json_encode( $data_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @param $uri
	 * @param $data
	 * @param $params
	 *
	 * @return array
	 */
	protected function get_request( $uri, $data, $params ) {
		$request = [
			'headers' => [ 'Authorization' => self::BEARER . $this->get_access_token() ],
			'uri'     => $uri,
			'data'    => $data,
			'params'  => $params,
		];

		$request['params'] = isset( $request['params'] ) && is_array( $request['params'] ) ? $request['params'] : [];

		$this->log_request( $data );

		return $request;
	}
}