<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';
require_once __DIR__ . '/../module/sdk/lib/class-unlimint-sdk.php';
require_once __DIR__ . '/../notification/class-wc-unlimint-notification-webhook.php';
require_once __DIR__ . '/class-wc-unlimint-subsections.php';
require_once __DIR__ . '/class-wc-unlimint-callback.php';
require_once __DIR__ . '/class-wc-unlimint-refund.php';
require_once __DIR__ . '/class-wc-unlimint-pix-gateway.php';
require_once __DIR__ . '/class-wc-unlimint-ticket-gateway.php';
require_once __DIR__ . '/class-wc-unlimint-files-registrar.php';

class WC_Unlimint_Gateway_Abstract extends WC_Payment_Gateway {

	public const COMMON_CONFIGS = [
		'_ul_public_key_test',
		'_ul_access_token_test',
		'_ul_public_key_prod',
		'_ul_access_token_prod',
		'checkout_country',
		'ul_statement_descriptor',
		'_ul_category_id',
		'_ul_store_identificator',
		'_ul_integrator_id',
		'_ul_custom_domain',
		'_ul_installments',
		'auto_return',
	];

	public const CREDENTIAL_FIELDS = [
		'_ul_public_key_test',
		'_ul_access_token_test',
		'_ul_public_key_prod',
		'_ul_access_token_prod',
	];

	public const ALLOWED_SECTIONS = [
		'woo-unlimint-custom',
		'woo-unlimint-ticket',
		'woo-unlimint-pix',
	];

	protected const RESPONSE_FOR_FAIL = [
		'result'   => 'fail',
		'redirect' => '',
	];

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $method_title;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var array
	 */
	public $ex_payments = [];

	/**
	 * @var string
	 */
	public $method;

	/**
	 * @var string
	 */
	public $method_description;

	/**
	 * @var string
	 */
	public $auto_return;

	/**
	 * @var string
	 */
	public $success_url;

	/**
	 * @var string
	 */
	public $pending_url;

	/**
	 * @var string
	 */
	public $installments;

	/**
	 * @var array
	 */
	public $form_fields;

	/**
	 * @var string
	 */
	public $payment_type;

	/**
	 * @var string
	 */
	public $checkout_type;

	/**
	 * @var int
	 */
	public $date_expiration;

	/**
	 * @var WC_Unlimint_Hook_Abstract
	 */
	public $hook;

	/**
	 * @var string[]
	 */
	public $supports;

	/**
	 * @var mixed
	 */
	public $icon;

	/**
	 * @var mixed|string
	 */
	public $ul_category_id;

	/**
	 * @var mixed|string
	 */
	public $store_identificator;

	/**
	 * @var mixed|string
	 */
	public $integrator_id;

	/**
	 * @var mixed|string
	 */
	public $debug_mode;

	/**
	 * @var mixed|string
	 */
	public $custom_domain;

	/**
	 * @var mixed|string
	 */
	public $tds_mode;

	/**
	 * @var WC_Unlimint_Logger
	 */
	public $logger;

	/**
	 * @var bool
	 */
	public $sandbox;

	/**
	 * @var Unlimint_Sdk
	 */
	public $unlimint_sdk;

	/**
	 * Terminal code test
	 *
	 * @var mixed|string
	 */
	public $ul_public_key_test;

	/**
	 * Terminal Password test
	 *
	 * @var mixed|string
	 */
	public $ul_access_token_test;

	/**
	 * Terminal code prod
	 *
	 * @var mixed|string
	 */
	public $ul_public_key_prod;

	/**
	 * Terminal Password prod
	 *
	 * @var mixed|string
	 */
	public $ul_access_token_prod;

	/**
	 * @var WC_Unlimint_Notification_Abstract
	 */
	public $notification;

	/**
	 * @var string
	 */
	public $checkout_country;

	/**
	 * @var string
	 */
	public $wc_country;

	/**
	 * @var string
	 */
	public $application_id;

	/**
	 * @var string
	 */
	public $type_payments;

	/**
	 * @var string|null
	 */
	public $logged_user_email;

	/**
	 * @var WC_Unlimint_Callback
	 */
	protected $callback;

	/**
	 * @var WC_Unlimint_Refund
	 */
	protected $refund;

	/**
	 * @var WC_Unlimint_Subsections
	 */
	protected $subsections;

	/**
	 * @var WC_Unlimint_Files_Registrar
	 */
	protected $files_registrar;

	/**
	 * @throws WC_Unlimint_Exception
	 */
	public function __construct() {
		if ( ! $this->is_valid_admin_section() ) {
			return;
		}

		$this->ul_public_key_test   = $this->get_option_ul( 'public_key_test' );
		$this->ul_access_token_test = $this->get_option_ul( 'access_token_test' );
		$this->ul_public_key_prod   = $this->get_option_ul( 'public_key_prod' );
		$this->installments         = $this->get_option_ul( 'installments' );
		$this->ul_access_token_prod = $this->get_option_ul( 'access_token_prod' );
		$this->debug_mode           = $this->get_option_ul( 'debug_mode', 'no' );
		$this->checkout_country     = get_option( 'checkout_country', '' );
		$this->wc_country           = get_option( 'woocommerce_default_country', '' );
		$this->ul_category_id       = $this->get_option_ul( 'category_id', 0 );
		$this->store_identificator  = $this->get_option_ul( 'store_identificator', 'WC-' );
		$this->integrator_id        = $this->get_option_ul( 'integrator_id', '' );
		$this->custom_domain        = $this->get_option_ul( 'custom_domain', '' );
		$this->tds_mode             = $this->get_option_ul( 'tds_mode', 'no' );
		$this->sandbox              = $this->is_test_user();
		$this->supports             = [ 'products', 'refunds' ];
		$this->logger               = new WC_Unlimint_Logger( $this );
		$this->application_id       = $this->get_application_id();
		$this->logged_user_email    = ( 0 !== wp_get_current_user()->ID ) ? wp_get_current_user()->user_email : null;
		$this->notification         = new WC_Unlimint_Notification_Webhook( $this );
		$this->unlimint_sdk         = new Unlimint_Sdk( $this->id );

		$this->subsections = new WC_Unlimint_Subsections();
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [
			$this->subsections,
			'form_is_saved'
		] );
		add_action( 'woocommerce_sections_checkout', [ $this->subsections, 'show_subsections_navigation' ] );

		$this->callback = new WC_Unlimint_Callback();
		add_action( 'woocommerce_api_unlimint_callback', [ $this->callback, 'process_callback' ] );

		$this->refund = new WC_Unlimint_Refund( $this->id );

		$this->files_registrar = new WC_Unlimint_Files_Registrar();
		$this->files_registrar->register_common_settings_js();
		$this->files_registrar->register_css();

		$this->init_settings();
	}

	public function get_gateway_title( $option_name = '' ) {
		if ( empty( $option_name ) ) {
			return $this->get_method_title();
		}

		$db_title = get_option( $option_name );
		if ( ! empty( $db_title ) ) {
			return $db_title;
		}

		return $this->get_method_title();
	}

	/**
	 * Terminal code
	 *
	 * @return mixed|string
	 */
	public function get_public_key() {
		if ( ! $this->is_production_mode() ) {
			return $this->ul_public_key_test;
		}

		return $this->ul_public_key_prod;
	}

	/**
	 * Load Unlimint options from DB
	 *
	 * @param string $key key.
	 * @param string $default default.
	 *
	 * @return mixed|string
	 */
	public function get_option_ul( $key, $default = '' ) {
		$key_ul    = "_ul_$key";
		$db_option = '';

		$wordpress_configs = self::COMMON_CONFIGS;
		if ( in_array( $key, $wordpress_configs, true ) ) {
			$db_option = get_option( $key, $default );
		} else if ( in_array( $key_ul, $wordpress_configs, true ) ) {
			$db_option = get_option( $key_ul, $default );
		} else {
			$option = get_option( $key, $default );
			if ( ! empty( $option ) ) {
				$db_option = $option;
			}

			$option = get_option( $key_ul, $default );
			if ( ! empty( $option ) ) {
				$db_option = $option;
			}
		}

		if ( ! empty( $db_option ) ) {
			return $db_option;
		}

		return get_option( $key, $default );
	}

	/**
	 * Validate section
	 *
	 * @return bool
	 */
	public function is_valid_admin_section() {
		if ( ! is_admin() || empty( $_GET['section'] ) ) {
			return true;
		}

		return ( $this->id === $_GET['section'] )
		       && in_array( $_GET['section'], self::ALLOWED_SECTIONS );
	}

	/**
	 * @return array
	 */
	public function field_title() {
		return [
			'title'       => __( 'Title', 'unlimint' ),
			'type'        => 'text',
			'description' => '',
			'class'       => 'hidden-field-ul-title ul-hidden-field',
			'default'     => $this->title,
		];
	}

	/**
	 * @param array $form_fields fields.
	 * @param array $ordination ordination.
	 *
	 * @return array
	 */
	public function sort_form_fields( $form_fields, $ordination ) {
		$array = [];
		foreach ( $ordination as $key ) {
			if ( ! isset( $form_fields[ $key ] ) ) {
				continue;
			}
			$array[ $key ] = $form_fields[ $key ];
			unset( $form_fields[ $key ] );
		}

		return array_merge_recursive( $array, $form_fields );
	}

	/**
	 * @return mixed|string
	 */
	public function get_application_id() {
		return $this->get_public_key();
	}

	/**
	 * @return bool
	 */
	public function is_available() {
		if ( ! did_action( 'wp_loaded' ) || ! isset( $this->settings['enabled'] ) ) {
			return false;
		}

		return 'yes' === $this->settings['enabled'];
	}

	/**
	 * @return string|void
	 */
	public function admin_url() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->id );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . get_class( $this ) );
	}

	/**
	 * @return bool
	 */
	public function is_test_user() {
		if ( $this->is_production_mode() ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function is_production_mode() {
		$this->update_credential_production();

		return $this->get_option_ul( 'checkout_credential_prod', get_option( 'checkout_credential_prod', 'no' ) ) === 'yes';
	}

	public function update_credential_production() {
		if ( ! empty( $this->get_option_ul( 'checkout_credential_prod', null ) ) ) {
			return;
		}

		foreach ( WC_Unlimint_Constants::PAYMENT_GATEWAYS as $gateway ) {
			$key     = 'woocommerce_' . $gateway::get_id() . '_settings';
			$options = get_option( $key );
			if ( ! empty( $options ) ) {
				if ( ! isset( $options['checkout_credential_production'] ) || empty( $options['checkout_credential_production'] ) ) {
					continue;
				}
				$options['checkout_credential_prod'] = $options['checkout_credential_production'];
				update_option( $key, apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $gateway::get_id(), $options ) );
			}
		}
	}

	/**
	 * @param WC_Order $order
	 * @param array $response
	 *
	 * @throws WC_Data_Exception
	 */
	protected function save_order_meta( $order, $response ) {
		$card_post_fields        = $_POST['unlimint_custom'];
		$installments_order_meta = (int) $card_post_fields[ WC_Unlimint_Module_Custom::INSTALLMENTS ];

		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_PAYMENT_TYPE_FIELDNAME, WC_Unlimint_Constants::PAYMENT_TYPE_PAYMENT );
		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_FIELD_INSTALLMENT_TYPE, get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_TYPE ) );

		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_GATEWAY_FIELDNAME, get_class( $this ) );
		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_COUNT_INSTALLMENT, $installments_order_meta );
		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_REDIRECT_URL_FIELDNAME, $response['redirect_url'] );
		WC_Unlimint_Helper::set_order_meta( $order, WC_Unlimint_Constants::ORDER_META_INITIAL_API_TOTAL, $order->get_total() );

		$order->set_transaction_id( $response['payment_data']['id'] );

		$order->save();
	}

	/**
	 * @param array $api_request
	 * @param array $post_fields
	 *
	 * @return mixed
	 */
	protected function call_api( $api_request, $post_fields ) {
		$this->logger->info( __FUNCTION__, 'call API' );

		$api_response = $this->unlimint_sdk->post( '/payments', wp_json_encode( $api_request ) );

		if ( $api_request['payment_method'] == "BANKCARD" && $api_response['response']['redirect_url'] ) {
			return $api_response['response'];
		}

		if ( (int) $api_response['status'] < 200 || (int) $api_response['status'] >= 300 || is_wp_error( $api_response ) ) {
			$this->logger->error( __FUNCTION__, 'Payment creation failed with an error: ' . $api_response['response']['message'] );

			return $api_response['response']['message'];
		}

		$this->logger->info( __FUNCTION__, 'payment link generated with success from Unlimint, with structure as follow: ' . wp_json_encode( $api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		return $api_response['response'];
	}

	/**
	 * @param array $api_response
	 * @param WC_Order $order
	 *
	 * @return array|string[]|void
	 * @throws WC_Data_Exception
	 */
	protected function handle_api_response( $api_response, $order ) {
		if ( is_array( $api_response ) ) {
			$api_response['status'] = 'pending';
			$this->save_order_meta( $order, $api_response );
			$redirect = $api_response['redirect_url'] ?? $order->get_checkout_order_received_url();

			return [
				'result'   => 'success',
				'redirect' => $redirect,
			];
		}

		$this->logger->error( __FUNCTION__, 'There is a technical issue with the payment, please try place order again' );

		wc_add_notice(
			'<p>' . __( 'There is a technical issue with the payment, please try place order again', 'unlimint' ) . '</p>',
			'error'
		);

		return self::RESPONSE_FOR_FAIL;
	}
}