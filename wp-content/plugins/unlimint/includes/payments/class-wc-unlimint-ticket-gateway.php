<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-boleto-fields.php';
require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-order-status-fields.php';
require_once __DIR__ . '/class-wc-unlimint-refund.php';

/**
 * Unlimint Boleto payment method
 */
class WC_Unlimint_Ticket_Gateway extends WC_Unlimint_Payment_Abstract {

	const GATEWAY_ID = 'woo-unlimint-ticket';

	private $boleto_fields;

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct();

		$this->boleto_fields = new WC_Unlimint_Admin_Boleto_Fields();

		$this->description        = __( 'Boleto payment method', 'unlimint' );
		$this->method_description = $this->description;
		$this->date_expiration    = (int) $this->get_option_ul( 'date_expiration', 3 );
		$this->type_payments      = $this->get_option_ul( 'type_payments', 'no' );
		$this->payment_type       = 'ticket';
		$this->checkout_type      = 'custom';
		$this->activated_payment  = self::get_activated_payment();

		$this->hook = new WC_Unlimint_Hook_Ticket( $this );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund->process_refund( $order_id, $amount, $reason );
	}

	public function get_title() {
		$this->title = parent::get_gateway_title( WC_Unlimint_Admin_Boleto_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE );

		return $this->title;
	}

	public function get_method_title() {
		$this->method_title = __( 'Boleto - Unlimint', 'unlimint' );

		return $this->method_title;
	}

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$this->load_settings_js( 'boleto', 'boleto_settings_unlimint.js' );

		if ( ! empty( $_GET[ WC_Unlimint_Subsections::SUBSECTION_GET_PARAM ] ) ) {
			$order_status_fields = new WC_Unlimint_Admin_Order_Status_Fields();

			return $order_status_fields->get_form_fields();
		}

		return $this->boleto_fields->get_form_fields();
	}

	/**
	 * Get fields sequence
	 *
	 * @return array
	 */
	public function get_fields_sequence() {
		return [
			// Necessary to run.
			'title',
			'description',
			// Checkout
			'checkout_ticket_header',
			'checkout_steps',
			// Country
			'checkout_country_title',
			'checkout_country',
			'checkout_btn_save',
			// Carga tus credenciales.
			// Configure the personalized payment experience in your store.
			'checkout_ticket_payments_title',
			'checkout_payments_subtitle',
			'checkout_ticket_payments_description',
			'enabled',
			'checkout_credential_title',
			'checkout_credential_mod_test_title',
			'checkout_credential_mod_test_description',
			'checkout_credential_mod_prod_title',
			'checkout_credential_mod_prod_description',
			'checkout_credential_prod',
			'checkout_credential_link',
			'checkout_credential_title_test',
			'checkout_credential_description_test',
			'_ul_public_key_test',
			'_ul_access_token_test',
			'checkout_credential_title_prod',
			'checkout_credential_description_prod',
			'_ul_public_key_prod',
			'_ul_access_token_prod',
			// No olvides de homologar tu cuenta.
			'checkout_homolog_title',
			'checkout_homolog_subtitle',
			'checkout_homolog_link',
			// Set up the payment experience in your store.
			'checkout_ticket_options_title',
			'ul_statement_descriptor',
			'_ul_category_id',
			'_ul_store_identificator',
			'_ul_integrator_id',
			// Advanced settings.
			'checkout_advanced_settings',
			'_ul_debug_mode',
			'_ul_custom_domain',
			'date_expiration',
			// Advanced configuration of the personalized payment experience.
			'checkout_ticket_payments_advanced_title',
			'checkout_payments_advanced_description',
			'stock_reduce_mode',
			// Support session.
			'checkout_support_title',
			'checkout_support_description',
			'checkout_support_description_link',
			'checkout_support_problem',
			// Everything ready for the takeoff of your sales?
			'checkout_ready_title',
			'checkout_ready_description',
			'checkout_ready_description_link',
		];
	}

	/**
	 * Get activated payment
	 *
	 * @return array
	 */
	public static function get_activated_payment() {
		$activated_payment          = [];
		$get_payment_methods_ticket = get_option( '_all_payment_methods_ticket', '' );

		if ( ! empty( $get_payment_methods_ticket ) ) {
			$saved_options = get_option( 'woocommerce_woo-unlimint-ticket_settings', '' );

			if ( ! is_array( $get_payment_methods_ticket ) ) {
				$get_payment_methods_ticket = json_decode( $get_payment_methods_ticket, true );
			}

			foreach ( $get_payment_methods_ticket as $payment_methods_ticket ) {
				$ticketId = 'ticket_payment_' . $payment_methods_ticket['id'];
				if ( ! isset( $saved_options[ $ticketId ] ) || 'yes' === $saved_options[ $ticketId ] ) {
					$activated_payment[] = $payment_methods_ticket;
				}
			}
		}

		return $activated_payment;
	}

	/**
	 * Payment fields
	 */
	public function payment_fields() {
		wp_enqueue_style(
			'unlimint-basic-checkout-styles',
			plugins_url( '../assets/css/basic_checkout_unlimint.css', plugin_dir_path( __FILE__ ) ),
			[],
			WC_Unlimint_Constants::VERSION
		);

		$amount = $this->get_order_total();

		$logged_user_email = ( 0 !== wp_get_current_user()->ID ) ? wp_get_current_user()->user_email : null;
		$address           = get_user_meta( wp_get_current_user()->ID, 'billing_address_1', true );
		$address_2         = get_user_meta( wp_get_current_user()->ID, 'billing_address_2', true );
		$address           .= ( ! empty( $address_2 ) ? ' - ' . $address_2 : '' );
		$country           = get_user_meta( wp_get_current_user()->ID, 'billing_country', true );
		$address           .= ( ! empty( $country ) ? ' - ' . $country : '' );

		$parameters = [
			'amount'               => $amount,
			'payment_methods'      => $this->activated_payment,
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

		wc_get_template( 'checkout/ticket-checkout.php', $parameters, 'woo/unlimint/module/', WC_Unlimint_Module::get_templates_path() );
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order Id.
	 *
	 * @return array|string[]
	 */
	public function process_payment( $order_id ) {
		$this->logger->info( __FUNCTION__, 'init.' );

		$response_for_fail = [
			'result'   => 'fail',
			'redirect' => '',
		];

		if ( ! isset( $_POST['unlimint_ticket'] ) ) {
			$this->logger->error( __FUNCTION__, 'A problem was occurred when processing your payment. Please, try again.' );
			wc_add_notice( '<p>' . __( 'Unlimint - A problem was occurred when processing your payment. Please, try again.', 'unlimint' ) . '</p>', 'error' );

			return $response_for_fail;
		}


		$order = wc_get_order( $order_id );

		$api_response = $this->call_api( $order, $_POST );

		if ( is_array( $api_response ) ) {
			$api_response['status'] = 'pending';

			$this->save_order_meta( $order, $api_response );

			return $this->handle_status( $api_response, $order );
		}

		$this->logger->info( __FUNCTION__, 'A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the checkout form? ' );

		wc_add_notice(
			'<p>' . __( 'A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the payment form?', 'unlimint' ) . '</p>',
			'error'
		);

		return $response_for_fail;
	}

	private function handle_status( $response, $order ) {
		$result = [];

		switch ( $response['status'] ) {
			case 'approved':
				WC()->cart->empty_cart();
				wc_add_notice( '<p>' . $this->get_order_status( 'accredited' ) . '</p>', 'notice' );

				$result = [
					'result'   => 'success',
					'redirect' => $order->get_checkout_order_received_url(),
				];
				break;

			case 'pending':
				// Order approved/pending, we just redirect to the thankyou page.
				$result = [
					'result'   => 'success',
					'redirect' => $order->get_checkout_order_received_url(),
				];
				break;

			case 'in_process':
				// For pending, we don't know if the purchase will be made, so we must inform this status.
				wc_add_notice(
					'<p>' . $this->get_order_status( $response['status_detail'] ) . '</p>' .
					'<p><a class="button" href="' . esc_url( $order->get_checkout_order_received_url() ) . '">' .
					__( 'See your order form', 'unlimint' ) .
					'</a></p>',
					'notice'
				);

				$result = [
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url( true ),
				];
				break;

			case 'rejected':
				// If rejected is received, the order will not proceed until another payment try, so we must inform this status.
				wc_add_notice(
					'<p>' . __(
						'Your payment was declined. You can try again.',
						'unlimint'
					) . '<br>' .
					$this->get_order_status( $response['status_detail'] ) .
					'</p>' .
					'<p><a class="button" href="' . esc_url( $order->get_checkout_payment_url() ) . '">' .
					__( 'Click to try again', 'unlimint' ) .
					'</a></p>',
					'error'
				);

				$result = [
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url( true ),
				];
				break;

			case 'cancelled':
			case 'in_mediation':
			case 'charged_back':
				// If we enter here (an order generating a direct [cancelled, in_mediation, or charged_back] status),
				// them there must be something very wrong!
				break;

			default:
				break;
		}

		return $result;
	}

	/**
	 * @param Order $order Order.
	 * @param array $post_fields Checkout info.
	 *
	 * @return string|array
	 * @throws Exception
	 */
	public function call_api( $order, $post_fields ) {
		$this->logger->info( __FUNCTION__, 'init' );

		$module_custom = new WC_Unlimint_Module_Ticket( $this, $order, $post_fields );

		$api_request = $module_custom->get_api_request();

		$api_response = $this->unlimint_sdk->post( '/payments', wp_json_encode( $api_request ) );


		if ( $api_response['status'] < 200 || $api_response['status'] >= 300 ) {
			$this->logger->error( __FUNCTION__, 'Payment creation failed with an error: ' . $api_response['response']['message'] );

			return $api_response['response']['message'];
		}

		if ( is_wp_error( $api_response ) ) {
			$this->logger->error( __FUNCTION__, 'WordPress error, payment creation failed with an error: ' . $api_response['response']['message'] );

			return $api_response['response']['message'];
		}

		$this->logger->info( __FUNCTION__, 'payment link generated with success from Unlimint, with structure as follow: ' . wp_json_encode( $api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

		return $api_response['response'];
	}

	public function handle_callback() {
		$this->callback->process_callback();
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}