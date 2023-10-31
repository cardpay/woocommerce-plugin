<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Unlimit_Rest_Client extends WC_Unlimit_Rest_Client_Abstract {

	/**
	 * @param array $request Request
	 *
	 * @return array|null
	 */
	public static function get( $request, $urlPrefix ) {
		$request['method'] = 'GET';

		return self::exec_abs( $request, $urlPrefix );
	}

	/**
	 * @param array $request Request
	 *
	 * @return array|null
	 */
	public static function post( $request, $urlPrefix ) {
		$request['method'] = 'POST';

		return self::exec_abs( $request, $urlPrefix );
	}

	/**
	 * @param array $request Request
	 * @param $urlPrefix
	 *
	 * @return array|null
	 */
	public static function patch( $request, $urlPrefix ) {
		$request['method'] = 'PATCH';

		return self::exec_abs( $request, $urlPrefix );
	}

	/**
	 * @param array $request Request
	 *
	 * @return array|null
	 */
	public static function put( $request, $urlPrefix ) {
		$request['method'] = 'PUT';

		return self::exec_abs( $request, $urlPrefix );
	}

	/**
	 * @param array $request Request
	 *
	 * @return array|null
	 */
	public static function delete( $request, $urlPrefix ) {
		$request['method'] = 'DELETE';

		return self::exec_abs( $request, $urlPrefix );
	}
}