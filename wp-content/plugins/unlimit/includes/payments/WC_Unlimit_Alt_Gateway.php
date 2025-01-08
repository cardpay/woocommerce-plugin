<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Order_Status_Fields.php';
require_once __DIR__ . '/WC_Unlimit_Refund.php';
require_once __DIR__ . '/WC_Unlimit_Files_Registrar.php';

defined( 'ABSPATH' ) || exit;

/**
 * Gateway for alternative payment methods
 */
class WC_Unlimit_Alt_Gateway extends WC_Unlimit_Gateway_Abstract {

	private $short_gateway_id;

	/**
	 * @var string
	 */
	protected $gateway_name;

	/**
	 * @var WC_Unlimit_Alt_Admin_Fields
	 */
	private $gateway_fields;

	public $date_expiration;

	public $unlimit_sdk;
	/**
	 * @var string|void
	 */
	public $method_description;

	public $payment_type;

	public $checkout_type;

	public function __construct( $gateway_id, $short_gateway_id, $gateway_name, $gateway_fields, $hook ) {
		$this->id                 = $gateway_id;
		$this->short_gateway_id   = $short_gateway_id;
		$this->gateway_name       = $gateway_name;
		$this->gateway_fields     = $gateway_fields;
		$this->description        = __( $gateway_name . ' payment method', 'unlimit' );
		$this->method_description = $this->description;
		$this->date_expiration    = (int) $this->get_option_ul( 'date_expiration', 3 );
		$this->type_payments      = $this->get_option_ul( 'typePayments', 'no' );
		$this->payment_type       = $short_gateway_id;
		$this->checkout_type      = 'custom';
		$this->notification       = new WC_Unlimit_Notification_Webhook( $this );

		$this->init_settings();
		$this->hook = $hook;
		$this->hook->load_hooks();

		parent::__construct();

		$gateway_name_lowercase = strtolower( $this->gateway_name );

		$this->files_registrar = new WC_Unlimit_Files_Registrar();
		$this->files_registrar->load_payment_form_script();

		$gateway_name_lowercase_replace = str_replace( ' ', '_', $gateway_name_lowercase );
		$this->files_registrar->register_settings_js( $gateway_name_lowercase_replace,
			$gateway_name_lowercase_replace . '_settings_unlimit.js' );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund->process_refund( $order_id, $amount, $reason );
	}

	public function can_refund_order( $order ) {
		// Checks if the order can be refunded.

		$payment_method  = $order->get_payment_method();
		$payment_methods = [
			'woo-unlimit-paypal',
			'woo-unlimit-gpay',
			'woo-unlimit-mbway',
			'woo-unlimit-apay',
		];

		$field_status_order = $order->get_status();
		$status_true        = [
			'processing',
			'completed',
		];

		if (
			in_array( $payment_method, $payment_methods ) &&
			in_array( $field_status_order, $status_true )
		) {
			return true;
		}

		return false;
	}

	public function get_title() {
		$this->title =
			$this->get_gateway_title(
				$this->gateway_fields->get_fieldname_prefix() .
				WC_Unlimit_Admin_Fields::FIELD_PAYMENT_TITLE
			);

		return $this->title;
	}

	public function get_method_title() {
		$this->method_title = __( $this->gateway_name . ' - Unlimit', 'unlimit' );

		return $this->method_title;
	}

	/**
	 * @return array
	 */
	public function get_form_fields() {
		if ( isset( $_REQUEST['woo-unlimit-apay'] ) ) {
			return $this->gateway_fields->get_form_fields( false, $this->settings );
		}

		return $this->gateway_fields->get_form_fields();
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() {
		$amount = $this->get_order_total();
		$user   = wp_get_current_user();

		$logged_user_email = $user->ID ? $user->user_email : null;

		$address_parts = [
			get_user_meta( $user->ID, 'billing_address_1', true ),
			get_user_meta( $user->ID, 'billing_address_2', true ),
			get_user_meta( $user->ID, 'billing_country', true ),
		];
		$address       = implode( ' - ', array_filter( $address_parts ) );

		$parameters = [
			'amount'               => $amount,
			'payer_email'          => esc_js( $logged_user_email ),
			'woocommerce_currency' => get_woocommerce_currency(),
			'febraban'             => $this->get_febraban_data( $user,
				$address ),
			'images_path'          => plugins_url( '../assets/images/',
				plugin_dir_path( __FILE__ ) ),
		];

		$payment_pages = [
			'woo-unlimit-gpay'       => [
				'google_merchant_id',
				get_option( WC_Unlimit_Admin_Gpay_Fields::GPAY_GOOGLE_MERCHANT_ID ) . ' ' . get_woocommerce_currency(),
			],
			'woo-unlimit-airteltz'     => [
				'is_airteltz_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-mbway'      => [
				'is_mbway_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-paypal'     => [
				'is_paypal_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-spei'       => [
				'is_spei_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-oxxo'       => [
				'is_oxxo_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-sepa'       => [
				'is_sepa_payment_page_required',
				'payment_page',
			],
			'woo-unlimit-multibanco' => [
				'is_multibanco_payment_page_required',
				'payment_page',
			],
		];

		if ( isset( $payment_pages[ $this->id ] ) ) {
			[ $param_key, $option_suffix ] = $payment_pages[ $this->id ];
			$parameters[ $param_key ] = $option_suffix === 'payment_page'
				? 'payment_page' === get_option(
					$this->get_field_prefix( $this->id ) . WC_Unlimit_Admin_Fields::FIELD_API_ACCESS_MODE
				)
				: $option_suffix;
		}

		wc_get_template(
			'checkout/' . $this->short_gateway_id . '-checkout.php',
			$parameters,
			'woo/unlimit/module/',
			WC_Unlimit_Module::get_templates_path()
		);
	}

	private function get_febraban_data( $user, $address ) {
		if ( ! $user->ID ) {
			return [
				'firstname' => '',
				'lastname'  => '',
				'docNumber' => '',
				'address'   => '',
				'number'    => '',
				'city'      => '',
				'state'     => '',
				'zipcode'   => '',
			];
		}

		return [
			'firstname' => esc_js( $user->user_firstname ),
			'lastname'  => esc_js( $user->user_lastname ),
			'docNumber' => '',
			'address'   => esc_js( $address ),
			'number'    => '',
			'city'      => esc_js( get_user_meta( $user->ID,
				'billing_city',
				true ) ),
			'state'     => esc_js( get_user_meta( $user->ID,
				'billing_state',
				true ) ),
			'zipcode'   => esc_js( get_user_meta( $user->ID,
				'billing_postcode',
				true ) ),
		];
	}

	private function get_field_prefix( $gateway_id ) {
		$prefix_map = [
			'woo-unlimit-airteltz'     => WC_Unlimit_Admin_Airteltz_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-mbway'      => WC_Unlimit_Admin_Mbway_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-paypal'     => WC_Unlimit_Admin_Paypal_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-spei'       => WC_Unlimit_Admin_Spei_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-oxxo'       => WC_Unlimit_Admin_Oxxo_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-sepa'       => WC_Unlimit_Admin_Sepa_Fields::FIELDNAME_PREFIX,
			'woo-unlimit-multibanco' => WC_Unlimit_Admin_Multibanco_Fields::FIELDNAME_PREFIX,
		];

		return $prefix_map[ $gateway_id ] ?? '';
	}

	/**
	 * Process payment
	 *
	 * @param  int  $order_id  Order Id.
	 *
	 * @return array|string[]
	 * @throws WC_Data_Exception
	 */
	public function process_payment( $order_id ) {
		$gateway_id       = 'unlimit_' . $this->short_gateway_id;
		$allowed_gateways = [ 'unlimit_ticket', 'unlimit_pix' ];

		if ( in_array( $gateway_id, $allowed_gateways ) && ! isset( $_POST[ $gateway_id ] ) ) {
			$this->logger->error(
				__FUNCTION__,
				'A problem occurred when processing your payment. Please, try again.'
			);
			wc_add_notice(
				'<p>' . __( 'A problem occurred when processing your payment. Please, try again.', 'unlimit' ) . '</p>',
				'error'
			);

			return self::RESPONSE_FOR_FAIL;
		}

		$order = wc_get_order( $order_id );

		$api_request  = $this->get_module( $order, $_POST )->get_api_request();
		$api_response = $this->call_api( $api_request );

		return $this->handle_api_response( $api_response, $order );
	}
}
