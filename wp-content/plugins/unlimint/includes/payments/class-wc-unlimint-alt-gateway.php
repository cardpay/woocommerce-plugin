<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-order-status-fields.php';
require_once __DIR__ . '/class-wc-unlimint-refund.php';
require_once __DIR__ . '/class-wc-unlimint-files-registrar.php';

/**
 * Gateway for alternative payment methods
 */
class WC_Unlimint_Alt_Gateway extends WC_Unlimint_Gateway_Abstract {

	private $short_gateway_id;

	/**
	 * @var string
	 */
	private $gateway_name;

	/**
	 * @var WC_Unlimint_Alt_Admin_Fields
	 */
	private $gateway_fields;

	public function __construct( $gateway_id, $short_gateway_id, $gateway_name, $gateway_fields, $hook ) {
		$this->id                 = $gateway_id;
		$this->short_gateway_id   = $short_gateway_id;
		$this->gateway_name       = $gateway_name;
		$this->gateway_fields     = $gateway_fields;
		$this->unlimint_sdk       = new Unlimint_Sdk( $this->id );
		$this->description        = __( $gateway_name . ' payment method', 'unlimint' );
		$this->method_description = $this->description;
		$this->date_expiration    = (int) $this->get_option_ul( 'date_expiration', 3 );
		$this->type_payments      = $this->get_option_ul( 'type_payments', 'no' );
		$this->payment_type       = $short_gateway_id;
		$this->checkout_type      = 'custom';
		$this->notification       = new WC_Unlimint_Notification_Webhook( $this );

		$this->init_settings();
		$this->hook = $hook;
		$this->hook->load_hooks();

		parent::__construct();

		$this->callback = new WC_Unlimint_Callback();
		add_action( 'woocommerce_api_unlimint_callback', [ $this, 'handle_callback' ] );

		$gateway_name_lowercase = strtolower( $this->gateway_name );

		$this->files_registrar = new WC_Unlimint_Files_Registrar();
		$this->files_registrar->register_settings_js( $gateway_name_lowercase, $gateway_name_lowercase . '_settings_unlimint.js' );
	}

	public function can_refund_order( $order ) {
		return false;
	}

	public function get_title() {
		$this->title = $this->get_gateway_title( $this->gateway_fields->get_fieldname_prefix() . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE );

		return $this->title;
	}

	public function get_method_title() {
		$this->method_title = __( $this->gateway_name . ' - Unlimint', 'unlimint' );

		return $this->method_title;
	}

	/**
	 * @return array
	 */
	public function get_form_fields() {
		if ( ! empty( $_GET[ WC_Unlimint_Subsections::SUBSECTION_GET_PARAM ] ) ) {
			$order_status_fields = new WC_Unlimint_Admin_Order_Status_Fields();

			return $order_status_fields->get_alt_form_fields();
		}

		return $this->gateway_fields->get_form_fields();
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() {
		$amount = $this->get_order_total();

		$logged_user_email = ( 0 !== wp_get_current_user()->ID ) ? wp_get_current_user()->user_email : null;
		$address           = get_user_meta( wp_get_current_user()->ID, 'billing_address_1', true );
		$address_2         = get_user_meta( wp_get_current_user()->ID, 'billing_address_2', true );
		$address           .= ( ! empty( $address_2 ) ? ' - ' . $address_2 : '' );
		$country           = get_user_meta( wp_get_current_user()->ID, 'billing_country', true );
		$address           .= ( ! empty( $country ) ? ' - ' . $country : '' );

		$parameters = [
			'amount'               => $amount,
			'payer_email'          => esc_js( $logged_user_email ),
			'woocommerce_currency' => get_woocommerce_currency(),
			'febraban'             => ( 0 !== wp_get_current_user()->ID ) ?
				[
					'firstname' => esc_js( wp_get_current_user()->user_firstname ),
					'lastname'  => esc_js( wp_get_current_user()->user_lastname ),
					'docNumber' => '',
					'address'   => esc_js( $address ),
					'number'    => '',
					'city'      => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_city', true ) ),
					'state'     => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_state', true ) ),
					'zipcode'   => esc_js( get_user_meta( wp_get_current_user()->ID, 'billing_postcode', true ) ),
				] :
				[
					'firstname' => '',
					'lastname'  => '',
					'docNumber' => '',
					'address'   => '',
					'number'    => '',
					'city'      => '',
					'state'     => '',
					'zipcode'   => '',
				],
			'images_path'          => plugins_url( '../assets/images/', plugin_dir_path( __FILE__ ) ),
		];

		wc_get_template( 'checkout/' . $this->short_gateway_id . '-checkout.php', $parameters, 'woo/unlimint/module/', WC_Unlimint_Module::get_templates_path() );
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order Id.
	 *
	 * @return array|string[]
	 * @throws WC_Data_Exception
	 */
	public function process_payment( $order_id ) {
		$this->logger->info( __FUNCTION__,
			'Alternative payment method, POST data: ' . wp_json_encode( $_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);

		$gateway_id = 'unlimint_' . $this->short_gateway_id;
		if ( ! isset( $_POST[ $gateway_id ] ) ) {
			$this->logger->error( __FUNCTION__, 'A problem was occurred when processing your payment. Please, try again.' );
			wc_add_notice( '<p>' . __( 'Unlimint - A problem was occurred when processing your payment. Please, try again.', 'unlimint' ) . '</p>', 'error' );

			return self::RESPONSE_FOR_FAIL;
		}

		$order = wc_get_order( $order_id );

		$api_request  = $this->get_module( $order, $_POST )->get_api_request();
		$api_response = $this->call_api( $api_request, $_POST );

		return $this->handle_api_response( $api_response, $order );
	}

	public function handle_callback() {
		$this->callback->process_callback();
	}
}