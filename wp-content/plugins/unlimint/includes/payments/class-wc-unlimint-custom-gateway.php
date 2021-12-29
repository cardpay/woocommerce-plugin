<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-bankcard-fields.php';
require_once __DIR__ . '/form_fields/class-wc-unlimint-admin-order-status-fields.php';
require_once __DIR__ . '/../module/class-wc-unlimint-installments.php';
require_once __DIR__ . '/../module/class-wc-unlimint-helper.php';
require_once __DIR__ . '/../module/config/class-wc-unlimint-constants.php';
require_once __DIR__ . '/class-wc-unlimint-callback.php';
require_once __DIR__ . '/class-wc-unlimint-subsections.php';
require_once __DIR__ . '/class-wc-unlimint-auth-payment.php';
require_once __DIR__ . '/class-wc-unlimint-refund.php';

/**
 * Unlimint 'Credit card' ('Bank card') payment method
 */
class WC_Unlimint_Custom_Gateway extends WC_Unlimint_Payment_Abstract {

	public const GATEWAY_ID = 'woo-unlimint-custom';

	private $bankcard_fields;

	/**
	 * @var WC_Unlimint_Auth_Payment
	 */
	private $auth_payment;

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct();

		$this->bankcard_fields = new WC_Unlimint_Admin_BankCard_Fields();

		$this->description = __( 'Credit card payment method', 'unlimint' );
		$this->method_description = $this->description;

		$this->hook         = new WC_Unlimint_Hook_Custom( $this );

		$this->register_auth_payment();
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund->process_refund( $order_id, $amount, $reason );
	}

	private function register_auth_payment() {
		$this->auth_payment = new WC_Unlimint_Auth_Payment();
		add_action( 'woocommerce_order_item_add_action_buttons', [ $this->auth_payment, 'show_auth_payment_buttons' ] );

		$this->auth_payment->do_payment_action();
	}

	public function get_title() {
		$this->title = parent::get_gateway_title( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE );

		return $this->title;
	}

	public function get_method_title() {
		$this->method_title = __( 'Credit Card - Unlimint', 'unlimint' );

		return $this->method_title;
	}

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$this->load_settings_js( 'bankcard', 'bankcard_settings_unlimint.js' );

		if ( ! empty( $_GET[ WC_Unlimint_Subsections::SUBSECTION_GET_PARAM ] ) ) {
			$order_status_fields = new WC_Unlimint_Admin_Order_Status_Fields();

			return $order_status_fields->get_form_fields();
		}

		return $this->bankcard_fields->get_form_fields();
	}

	/**
	 * @return array
	 */
	public function get_fields_sequence() {
		return [
			// Necessary to run.
			'title',
			'description',
			// Checkout
			'checkout_custom_header',
			'checkout_steps',
			// Configure the personalized payment experience in your store.
			'checkout_custom_payments_title',
			'checkout_payments_subtitle',
			'enabled',
			// Country
			'checkout_country_title',
			'checkout_country',
			'checkout_btn_save',
			// Credentials.
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
			'checkout_custom_options_title',
			'_ul_installments',
			'ul_statement_descriptor',
			'_ul_category_id',
			'_ul_store_identificator',
			'_ul_integrator_id',
			// Advanced settings.
			'checkout_advanced_settings',
			'_ul_debug_mode',
			'_ul_custom_domain',
			// Advanced configuration of the personalized payment experience.
			'checkout_custom_payments_advanced_title',
			'checkout_payments_advanced_description',
			//'3ds_mode',
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
	 * Field checkout custom header
	 *
	 * @return array
	 */
	public function field_checkout_custom_header() {
		return [
			'title' => sprintf(
			/* translators: %s card */
				__( 'Checkout of payments with credit cards %s', 'unlimint' ),
				'<div class="ul-row">
                <div class="ul-col-md-12 ul_subtitle_header">
                ' . __( 'Accept payments instantly and maximize the conversion of your business', 'unlimint' ) . '
                 </div>
              <div class="ul-col-md-12">
                <p class="ul-text-checkout-body ul-mb-0">
                  ' . __( 'Turn your online store into a secure and easy-to-use payment gateway for your customers. With personalized checkout your customers pay without leaving your store!', 'unlimint' ) . '
                </p>
              </div>
            </div>'
			),
			'type'  => 'title',
			'class' => 'ul_title_header',
		];
	}

	/**
	 * @return array
	 */
	public function field_checkout_custom_payments_title() {
		return [
			'title' => __( 'Unlimint Credit Card', 'unlimint' ),
			'type'  => 'title',
			'class' => 'ul_title_bd',
		];
	}

	/**
	 * @return array
	 */
	public function field_checkout_custom_payments_advanced_title() {
		return [
			'title' => __( 'Advanced configuration of the personalized payment experience', 'unlimint' ),
			'type'  => 'title',
			'class' => 'ul_subtitle_bd',
		];
	}

	/**
	 * @param string $status_detail Status.
	 *
	 * @return string|void
	 */
	public function get_order_status( $status_detail ) {
		switch ( $status_detail ) {
			case 'accredited':
				$order_status = __( 'That’s it, payment accepted!', 'unlimint' );
				break;

			case 'pending_contingency':
				$order_status = __( 'We are processing your payment. In less than an hour we will send you the result by email.', 'unlimint' );
				break;

			case 'pending_review_manual':
				$order_status = __( 'We are processing your payment. In less than 2 days we will send you by email if the payment has been approved or if additional information is needed.', 'unlimint' );
				break;

			case 'cc_rejected_bad_filled_card_number':
				$order_status = __( 'Check the card number.', 'unlimint' );
				break;

			case 'cc_rejected_bad_filled_date':
				$order_status = __( 'Check the expiration date.', 'unlimint' );
				break;

			case 'cc_rejected_bad_filled_other':
				$order_status = __( 'Check the information provided.', 'unlimint' );
				break;

			case 'cc_rejected_bad_filled_security_code':
				$order_status = __( 'Check the informed security code.', 'unlimint' );
				break;

			case 'cc_rejected_blacklist':
			case 'cc_rejected_card_error':
				$order_status = __( 'Your payment cannot be processed.', 'unlimint' );
				break;

			case 'cc_rejected_call_for_authorize':
				$order_status = __( 'You must authorize payments for your orders.', 'unlimint' );
				break;

			case 'cc_rejected_card_disabled':
				$order_status = __( 'Contact your card issuer to activate it. The phone is on the back of your card.', 'unlimint' );
				break;

			case 'cc_rejected_duplicated_payment':
				$order_status = __( 'You have already made a payment of this amount. If you have to pay again, use another card or other method of payment.', 'unlimint' );
				break;

			case 'cc_rejected_high_risk':
				$order_status = __( 'Your payment was declined. Please select another payment method. It is recommended in cash.', 'unlimint' );
				break;

			case 'cc_rejected_insufficient_amount':
				$order_status = __( 'Your payment does not have sufficient funds.', 'unlimint' );
				break;

			case 'cc_rejected_invalid_installments':
				$order_status = __( 'Payment cannot process the selected fee.', 'unlimint' );
				break;

			case 'cc_rejected_max_attempts':
				$order_status = __( 'You have reached the limit of allowed attempts. Choose another card or other payment method.', 'unlimint' );
				break;

			case 'cc_rejected_other_reason':
			default:
				$order_status = __( 'This payment method cannot process your payment.', 'unlimint' );
				break;
		}

		return $order_status;
	}

	/**
	 * Payment Fields
	 */
	public function payment_fields() {
		wp_enqueue_style(
			'unlimint-basic-checkout-styles',
			plugins_url( '../assets/css/basic_checkout_unlimint.css', plugin_dir_path( __FILE__ ) ),
			[],
			WC_Unlimint_Constants::VERSION
		);

		$installments_instance = new WC_Unlimint_Installments();

		$fieldname_prefix         = WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX;
		$are_installments_enabled = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) );
		$is_cpf_required          = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_ASK_CPF ) );

		$parameters = [
			'amount'                   => $this->get_order_total(),
			'public_key'               => $this->get_public_key(),
			'installments'             => $this->get_option_ul( '_ul_installments' ),
			'payer_email'              => esc_js( $this->logged_user_email ),
			'images_path'              => plugins_url( '../assets/images/', plugin_dir_path( __FILE__ ) ),
			'woocommerce_currency'     => get_woocommerce_currency(),
			'installment_options'      => $installments_instance->get_installment_options(),
			'are_installments_enabled' => $are_installments_enabled,
			'is_cpf_required'          => $is_cpf_required,
		];

		wc_get_template( 'checkout/custom-checkout.php', $parameters, 'woo/unlimint/module/', WC_Unlimint_Module::get_templates_path() );
	}

	/**
	 * @param int $order_id Order Id.
	 *
	 * @return array|void
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {
		$this->logger->info( __FUNCTION__, 'init.' );
		$this->log_post_data();

		$response_for_fail = [ 'result' => 'fail', 'redirect' => '' ];

		if ( ! isset( $_POST['unlimint_custom'] ) ) {
			$this->logger->error( __FUNCTION__, 'A problem was occurred when processing your payment. Please, try again.' );
			wc_add_notice( '<p>' . __( 'Unlimint - A problem was occurred when processing your payment. Please, try again.', 'unlimint' ) . '</p>', 'error' );

			return $response_for_fail;
		}

		$order = wc_get_order( $order_id );

		$api_response = $this->call_api( $order, $_POST );

		if ( is_array( $api_response ) && ( array_key_exists( 'payment_data', $api_response ) || array_key_exists( 'recurring_data', $api_response ) ) ) {
			$api_response['status'] = 'pending';

			$order->add_order_note(
				'Unlimint: ' .
				__( 'To confirm the payment click', 'unlimint' ) .
				' <a target="_blank" href="' . $api_response['redirect_url'] . '">' . __( 'here', 'unlimint' ) . '</a>',
				1
			);

			$this->save_order_meta( $order, $api_response );

			return $this->handle_status( $api_response, $order );
		}

		$this->logger->error( __FUNCTION__, 'A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the checkout form? ' );

		wc_add_notice(
			'<p>' . __( 'A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the payment form?', 'unlimint' ) . '</p>',
			'error'
		);

		return $response_for_fail;
	}

	private function log_post_data() {
		$post_data_for_log = $_POST;
		if ( isset( $post_data_for_log['unlimint_custom']['cardNumber'] ) ) {
			$post_data_for_log['unlimint_custom']['cardNumber'] = WC_Unlimint_Helper::mask_card_pan( $post_data_for_log['unlimint_custom']['cardNumber'] );
		}
		if ( isset( $post_data_for_log['unlimint_custom']['cvc'] ) ) {
			$post_data_for_log['unlimint_custom']['cvc'] = WC_Unlimint_Constants::SECURITY_CODE_MASKED;
		}

		$this->logger->info( __FUNCTION__, 'Bank card, POST data: ' . wp_json_encode( $post_data_for_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @param $response
	 * @param $order
	 *
	 * @return array|void
	 */
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
	 * @param WC_Order $order Order
	 * @param array $post_fields Checkout info
	 *
	 * @return string|array
	 * @throws Exception
	 */
	public function call_api( $order, $post_fields ) {
		$this->logger->info( __FUNCTION__, 'init' );

		$module_custom = new WC_Unlimint_Module_Custom( $this, $order, $post_fields );

		$api_request = $module_custom->get_api_request();

		$installments = (int) $post_fields['unlimint_custom']['installments'];
		if ( $installments > 1 ) {
			$api_response = $this->unlimint_sdk->post( '/installments', wp_json_encode( $api_request ) );
		} else {
			$api_response = $this->unlimint_sdk->post( '/payments', wp_json_encode( $api_request ) );
		}

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

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}