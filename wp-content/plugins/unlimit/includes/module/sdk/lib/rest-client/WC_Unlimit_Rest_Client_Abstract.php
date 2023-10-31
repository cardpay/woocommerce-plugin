<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Unlimit_Rest_Client_Abstract {

	/**
	 * E-mail admin
	 *
	 * @var string
	 */
	public static $email_admin = '';

	/**
	 * Site locale
	 *
	 * @var string
	 */
	public static $site_locale = '';

	/**
	 * Exec ABS
	 *
	 * @param array $request Request.
	 * @param string $url_prefix URL.
	 *
	 * @return array|null
	 */
	public static function exec_abs( $request, $url_prefix ) {
		$logger = new WC_Unlimit_Logger();
		$logger->info( __FUNCTION__, 'URL: ' . wp_json_encode( $url_prefix, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		try {
			$connect = self::build_request( $request, $url_prefix );
			if ( ! $connect ) {
				return null;
			}

			$response = self::execute( $connect );
			$logger->info(
				__FUNCTION__,
				'Response: ' .
				wp_json_encode(
					$response,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
				)
			);

			return $response;

		} catch ( Exception $e ) {
			$logger->error( __FUNCTION__, 'Exception: ' . wp_json_encode( $e, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

			return null;
		}
	}

	/**
	 * Build request
	 *
	 * @param array $request Request data.
	 * @param string $url_prefix URL.
	 *
	 * @return false|resource
	 * @throws WC_Unlimit_Exception Build request exception.
	 */
	public static function build_request( $request, $url_prefix ) {
		self::validate_request( $request );

		$headers              = [ 'accept: application/json' ];
		$json_content         = true;
		$form_content         = false;
		$default_content_type = true;

		if ( isset( $request['headers'] ) && is_array( $request['headers'] ) ) {
			foreach ( $request['headers'] as $header => $value ) {
				if ( 'content-type' === $header ) {
					$default_content_type = false;
					$json_content         = ( 'application/json' === $value );
					$form_content         = ( 'application/x-www-form-urlencoded' === $value );
				}
				$headers[] = $header . ': ' . $value;
			}
		}

		if ( $default_content_type ) {
			$headers[] = 'content-type: application/json';
		}

		/**
		 * @var resource|false|CurlHandle
		 */
		$connect = curl_init();
		curl_setopt( $connect, CURLOPT_USERAGENT, 'UnlimitPlugin/' . WC_Unlimit_Constants::VERSION . '/Woocommerce' );
		curl_setopt( $connect, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $connect, CURLOPT_HTTPHEADER, $headers );

		if ( isset( $request['method'] ) ) {
			curl_setopt( $connect, CURLOPT_CUSTOMREQUEST, $request['method'] );
		}

		if ( isset( $request['params'] ) && is_array( $request['params'] ) && count( $request['params'] ) > 0 ) {
			$request['uri'] .= ( strpos( $request['uri'], '?' ) === false ) ? '?' : '&';
			$request['uri'] .= self::build_query( $request['params'] );
		}
		curl_setopt( $connect, CURLOPT_URL, $url_prefix . $request['uri'] );

		self::assign_request_data( $request, $json_content, $form_content, $connect );

		return $connect;
	}

	/**
	 * @throws WC_Unlimit_Exception
	 */
	private static function assign_request_data( $request, $json_content, $form_content, $connect ): void {
		if ( ! isset( $request['data'] ) ) {
			return;
		}

		if ( $json_content ) {
			if ( is_string( $request['data'] ) ) {
				json_decode( $request['data'], true );
			} else {
				$request['data'] = wp_json_encode( $request['data'] );
			}
			if ( function_exists( 'json_last_error' ) ) {
				$json_error = json_last_error();
				if ( JSON_ERROR_NONE !== $json_error ) {
					throw new WC_Unlimit_Exception( "JSON Error [$json_error] - Data: " . $request['data'] );
				}
			}
		} elseif ( $form_content ) {
			$request['data'] = self::build_query( $request['data'] );
		}

		curl_setopt( $connect, CURLOPT_POSTFIELDS, $request['data'] );
	}

	/**
	 * Execute curl
	 *
	 * @param CurlHandle $connect Curl Handle Connection.
	 *
	 * @return array|null
	 * @throws WC_Unlimit_Exception Execute call exception.
	 */
	public static function execute( $connect ) {
		$response   = null;
		$api_result = curl_exec( $connect );
		if ( curl_errno( $connect ) ) {
			throw new WC_Unlimit_Exception( curl_error( $connect ) );
		}
		$api_http_code = curl_getinfo( $connect, CURLINFO_HTTP_CODE );

		if ( null !== $api_http_code && null !== $api_result ) {
			$response = [
				'status'   => $api_http_code,
				'response' => json_decode( $api_result, true ),
			];
		}

		curl_close( $connect );

		return $response;
	}

	/**
	 * Build query
	 *
	 * @param array $params Params.
	 *
	 * @return string
	 */
	public static function build_query( $params ) {
		if ( function_exists( 'http_build_query' ) ) {
			return http_build_query( $params );
		}

		foreach ( $params as $name => $value ) {
			$elements[] = "$name=" . rawurldecode( $value );
		}

		return implode( '&', $elements );
	}

	/**
	 * Set e-mail
	 *
	 * @param string $email E-mail.
	 */
	public static function set_email( $email ) {
		self::$email_admin = $email;
	}

	/**
	 * Set Country code
	 *
	 * @param string $country_code Country code.
	 */
	public static function set_locale( $country_code ) {
		self::$site_locale = $country_code;
	}

	/**
	 * @param array $request
	 *
	 * @throws WC_Unlimit_Exception
	 */
	private static function validate_request( $request = [] ) {
		if ( ! extension_loaded( 'curl' ) ) {
			throw new WC_Unlimit_Exception(
				'cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.'
			);
		}

		if ( ! isset( $request['method'] ) ) {
			throw new WC_Unlimit_Exception( 'No HTTP METHOD specified' );
		}

		if ( ! isset( $request['uri'] ) ) {
			throw new WC_Unlimit_Exception( 'No URI specified' );
		}
	}
}
