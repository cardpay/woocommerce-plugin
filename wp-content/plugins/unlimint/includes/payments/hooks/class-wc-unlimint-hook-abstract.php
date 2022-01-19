<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/../../module/class-wc-unlimint-helper.php';

abstract class WC_Unlimint_Hook_Abstract {

	/**
	 * @var WC_Unlimint_Gateway_Abstract
	 */
	public $gateway;

	/**
	 * @var string
	 */
	public $class;

	/**
	 * @var Unlimint_Sdk|null
	 */
	public $ul_instance;

	/**
	 * @var string
	 */
	public $public_key;

	/**
	 * @var string
	 */
	public $test_user;

	/**
	 * @var string
	 */
	public $site_id;

	/**
	 * @param WC_Unlimint_Gateway_Abstract $gateway Payment method.
	 */
	public function __construct( $gateway ) {
		$this->gateway     = $gateway;
		$this->class       = get_class( $gateway );
		$this->ul_instance = $gateway->unlimint_sdk;
		$this->public_key  = $gateway->get_public_key();
		$this->test_user   = get_option( '_test_user_v1' );
		$this->site_id     = get_option( '_site_id_v1' );
	}

	public function load_hooks() {
		wp_enqueue_script(
			'unlimint-lib-checkout',
			plugins_url( '../../assets/js/unlimint-lib.js', plugin_dir_path( __FILE__ ) ),
			[ 'jquery' ],
			WC_Unlimint_Constants::VERSION,
		);

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->gateway->id, [
			$this,
			'custom_process_admin_options'
		] );

		add_filter( 'woocommerce_gateway_title', [ $this, 'get_payment_method_title' ], 10, 2 );
	}

	/**
	 * @param string $title Title.
	 *
	 * @return string
	 */
	public function get_payment_method_title( $title ) {
		return $title;
	}

	/**
	 * @return bool
	 */
	public function custom_process_admin_options() {
		$this->gateway->init_settings();
		$post_data = $this->gateway->get_post_data();

		foreach ( $this->gateway->get_form_fields() as $key => $field ) {
			$value = $this->gateway->get_field_value( $key, $field, $post_data );

			update_option( $key, $value, true );

			$value                           = $this->gateway->get_field_value( $key, $field, $post_data );
			$this->gateway->settings[ $key ] = $value;
		}

		return update_option( $this->gateway->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->gateway->id, $this->gateway->settings ) );
	}

	public function notice_invalid_test_credentials() {
		$type    = 'error';
		$message = __( '<b>Terminal Password</b> test credential is invalid. Review the field to perform tests in your store.', 'unlimint' );
		WC_Unlimint_Notices::get_alert_frame( $message, $type );
	}

	public function notice_blank_test_credentials() {
		$type    = 'error';
		$message = __( '<b>Terminal Password</b> test credential is blank. Review the field to perform tests in your store.', 'unlimint' );
		WC_Unlimint_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * @param string $order_id Order Id
	 */
	public function redirect_to_api_url( $order_id ) {
		$data = $_GET;

		if ( ! isset( $data['noredir'] ) ) {
			$order        = wc_get_order( $order_id );
			$redirect_url = WC_Unlimint_Helper::get_order_meta( $order, WC_Unlimint_Constants::ORDER_META_REDIRECT_URL_FIELDNAME );

			wp_redirect( $redirect_url );
		}
	}
}