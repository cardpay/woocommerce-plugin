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
require_once __DIR__ . '/class-wc-unlimint-files-registrar.php';

/**
 * Unlimint 'Credit card' ('Bank card') payment method
 */
class WC_Unlimint_Custom_Gateway extends WC_Unlimint_Gateway_Abstract {

	public const GATEWAY_ID = 'woo-unlimint-custom';
	/**
	 * @var WC_Unlimint_Admin_BankCard_Fields
	 */
	private $bankcard_fields;

	/**
	 * @var WC_Unlimint_Auth_Payment
	 */
	private $auth_payment;

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		parent::__construct();

		$this->bankcard_fields = new WC_Unlimint_Admin_BankCard_Fields();

		$this->description        = __( 'Credit card payment method', 'unlimint' );
		$this->method_description = $this->description;

		$this->hook = new WC_Unlimint_Hook_Custom( $this );
		$this->hook->load_hooks();

		$this->register_auth_payment();

		$this->files_registrar = new WC_Unlimint_Files_Registrar();
		$this->files_registrar->register_settings_js( 'bankcard', 'bankcard_settings_unlimint.js' );
	}

	public function can_refund_order( $order ) {
		$field_installment_type       = $order->get_meta( '_ul_field_installment_type' );
		$field_count_installment_type = $order->get_meta( '_ul_field_count_installment_type' );

		if ( $field_installment_type == 'IF' || ( $field_installment_type == 'MF_HOLD' && $field_count_installment_type == 1 ) ) {
			return true;
		}

		return false;
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund->process_refund( $order_id, $amount, $reason );
	}

	private function register_auth_payment() {
		$this->auth_payment = new WC_Unlimint_Auth_Payment();
		add_action( 'woocommerce_order_item_add_action_buttons', [ $this->auth_payment, 'show_auth_payment_buttons' ] );
	}

	public function get_title() {
		$this->title = $this->get_gateway_title( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE );

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
		if ( ! empty( $_GET[ WC_Unlimint_Subsections::SUBSECTION_GET_PARAM ] ) ) {
			$order_status_fields = new WC_Unlimint_Admin_Order_Status_Fields();

			return $order_status_fields->get_card_form_fields();
		}

		return $this->bankcard_fields->get_form_fields();
	}

	/**
	 * Payment Fields
	 */
	public function payment_fields() {
		$installments_instance = new WC_Unlimint_Installments();

		$fieldname_prefix         = WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX;
		$are_installments_enabled = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) );
		$is_cpf_required          = ( 'yes' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_ASK_CPF ) );
		$is_payment_page_required = ( 'payment_page' === get_option( $fieldname_prefix . WC_Unlimint_Admin_BankCard_Fields::FIELD_API_ACCESS_MODE ) );

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
			'is_payment_page_required' => $is_payment_page_required,
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
		$this->log_post_data();
		$is_payment_page_required = ( 'payment_page' === get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_API_ACCESS_MODE ) );
		$are_installments_enabled = ( 'yes' === get_option( WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED ) );

		$post_can_be_empty = ( $is_payment_page_required || ! $are_installments_enabled );

		if ( ( ! isset( $_POST['unlimint_custom'] ) ) && ! $post_can_be_empty ) {
			$this->logger->error( __FUNCTION__, 'A problem was occurred when processing your payment. Please, try again.' );
			wc_add_notice( '<p>' . __( 'Unlimint - A problem was occurred when processing your payment. Please, try again.', 'unlimint' ) . '</p>', 'error' );

			return self::RESPONSE_FOR_FAIL;
		}

		$order = wc_get_order( $order_id );

		$module_custom = new WC_Unlimint_Module_Custom( $this, $order, $_POST, $post_can_be_empty );
		$api_request   = $module_custom->get_api_request();

		$api_response = $this->call_api( $api_request, $_POST );

		return $this->handle_api_response( $api_response, $order );
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
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}