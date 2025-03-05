<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_BankCard_Fields.php';
require_once __DIR__ . '/form_fields/WC_Unlimit_Admin_Order_Status_Fields.php';
require_once __DIR__ . '/../module/WC_Unlimit_Installments.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';
require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/WC_Unlimit_Callback.php';
require_once __DIR__ . '/WC_Unlimit_Subsections.php';
require_once __DIR__ . '/WC_Unlimit_Auth_Payment.php';
require_once __DIR__ . '/WC_Unlimit_Refund.php';
require_once __DIR__ . '/WC_Unlimit_Files_Registrar.php';

/**
 * Unlimit 'Credit card' ('Bank card') payment method
 */
class WC_Unlimit_Custom_Gateway extends WC_Unlimit_Gateway_Abstract {

	public const GATEWAY_ID = 'woo-unlimit-custom';
	/**
	 * @var WC_Unlimit_Admin_BankCard_Fields
	 */
	private $bankcard_fields;

	public function __construct() {
		$this->id = self::GATEWAY_ID;

		$this->bankcard_fields = new WC_Unlimit_Admin_BankCard_Fields();

		$this->description        = __( 'Credit card payment method', 'unlimit' );
		$this->method_description = $this->description;

		$this->hook = new WC_Unlimit_Hook_Custom( $this );

		parent::__construct();

		$this->hook->load_hooks();

		$this->register_auth_payment();

		$this->files_registrar = new WC_Unlimit_Files_Registrar();
		$this->files_registrar->load_payment_form_script();
		$this->files_registrar->register_settings_js( 'bankcard', 'bankcard_settings_unlimit.js' );
	}

	public function can_refund_order( $order ) {
		$can_refund = false;

		$field_status_order = $order->get_status();
		$status_true        = [
			'processing',
			'completed',
		];
		if ( ! in_array( $field_status_order, $status_true ) ) {
			return false;
		}

		$field_installment_type       = $order->get_meta( '_ul_field_installment_type' );
		$field_count_installment_type = $order->get_meta( '_ul_field_count_installment_type' );
		if (
			$field_installment_type == 'IF' ||
			( $field_installment_type == 'MF_HOLD' && $field_count_installment_type == 1 )
		) {
			$can_refund = true;
		}

		$are_installments_enabled = (
			'yes' === get_option(
				WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED
			)
		);
		if ( ! $are_installments_enabled ) {
			$can_refund = true;
		}

		return $can_refund;
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund->process_refund( $order_id, $amount, $reason );
	}

	private function register_auth_payment() {
		$auth_payment = new WC_Unlimit_Auth_Payment();
		add_action( 'woocommerce_order_item_add_action_buttons', [ $auth_payment, 'show_auth_payment_buttons' ] );
	}

	public function get_title() {
		$this->title = $this->get_gateway_title(
			WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
			WC_Unlimit_Admin_Fields::FIELD_PAYMENT_TITLE
		);

		return $this->title;
	}

	public function get_method_title() {
		$this->method_title = __( 'Credit card - Unlimit', 'unlimit' );

		return $this->method_title;
	}

	/**
	 * @return array
	 */
	public function get_form_fields() {
		return $this->bankcard_fields->get_form_fields();
	}

	/**
	 * Payment Fields
	 */
	public function payment_fields() {
		$installments_instance = new WC_Unlimit_Installments();

		$fieldname_prefix         = WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX;
		$are_installments_enabled = (
			'yes' === get_option(
				$fieldname_prefix .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED
			)
		);
		$is_cpf_required          = (
			'yes' === get_option(
				$fieldname_prefix . WC_Unlimit_Admin_BankCard_Fields::FIELD_ASK_CPF
			)
		);
		$is_payment_page_required = (
			'payment_page' === get_option(
				$fieldname_prefix .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_API_ACCESS_MODE
			)
		);
		$is_payment_mode_embedded = (
            'embedded' === get_option(
                $fieldname_prefix .
                WC_Unlimit_Admin_BankCard_Fields::FIELD_PAYMENT_MODE
            )
        );
		$is_recurring_enabled     = (
			'yes' === get_option(
				$fieldname_prefix .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_RECURRING_ENABLED
			)
		);

		if ( ! is_user_logged_in() ) {
			$is_recurring_enabled = false;
		}

		$customer_id         = get_current_user_id();
		$existing_filing_ids = [];
		if ( $is_recurring_enabled && $customer_id ) {
			global $wpdb;

			$terminal_code       = get_option(
				WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_TERMINAL_CODE
			);
			$table_name          = $wpdb->prefix . 'ul_recurring_data';
			$existing_filing_ids = $wpdb->get_results(
				'SELECT * FROM `' . $table_name . '` WHERE `customer_id` = ' . (int) $customer_id .
				' AND `updated_at` > "' . date( 'Y-m-d H:i:s', strtotime( '-1 year' ) ) . '"' .
				' AND `terminal_code` = "' . esc_sql( $terminal_code ) . '"' .
				' GROUP BY `masked_pan`, `card_type`, `terminal_code`',
				ARRAY_A
			);
		}

		$parameters = [
			'amount'                   => $this->get_order_total(),
			'public_key'               => $this->get_public_key(),
			'installments'             => $this->get_option_ul( '_ul_installments' ),
			'payer_email'              => esc_js( $this->logged_user_email ),
			'images_path'              => plugins_url( '../assets/images/', plugin_dir_path( __FILE__ ) ),
			'woocommerce_currency'     => get_woocommerce_currency(),
			'installment_options'      => $installments_instance->get_installment_options(),
			'are_installments_enabled' => $are_installments_enabled,
			'is_recurring_enabled'     => $is_recurring_enabled,
			'existing_filing_ids'      => $existing_filing_ids,
			'is_cpf_required'          => $is_cpf_required,
			'is_payment_page_required' => $is_payment_page_required,
			'is_payment_mode_embedded' => $is_payment_mode_embedded,
		];

		wc_get_template(
			'checkout/custom-checkout.php',
			$parameters,
			'woo/unlimit/module/',
			WC_Unlimit_Module::get_templates_path()
		);
	}

	/**
	 * @param int $order_id Order Id.
	 *
	 * @return array|void
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {
		$this->log_post_data();
		$is_payment_page_required = (
			'payment_page' === get_option(
				WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_API_ACCESS_MODE
			)
		);
		$are_installments_enabled = (
			'yes' === get_option(
				WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED
			)
		);

		$post_can_be_empty = ( $is_payment_page_required || ! $are_installments_enabled );

		if ( ( ! isset( $_POST['unlimit_custom'] ) ) && ! $post_can_be_empty ) {
			$this->logger->error( __FUNCTION__,
				'A problem was occurred when processing your payment. Please, try again.' );
			wc_add_notice(
				'<p>' . __( 'Unlimit - A problem was occurred when processing your payment.
				 Please, try again.',
					'unlimit' ) . '</p>',
				'error'
			);

			return self::RESPONSE_FOR_FAIL;
		}

		$order = wc_get_order( $order_id );

		$module_custom = new WC_Unlimit_Module_Custom( $this, $order, $_POST, $post_can_be_empty );
		$api_request   = $module_custom->get_api_request();

		$api_response = $this->call_api( $api_request );

		return $this->handle_api_response( $api_response, $order );
	}

	private function log_post_data() {
		$post_data_for_log = $_POST;
		if ( isset( $post_data_for_log['unlimit_custom']['cardNumber'] ) ) {
			$post_data_for_log['unlimit_custom']['cardNumber'] =
				WC_Unlimit_Helper::mask_card_pan(
					$post_data_for_log['unlimit_custom']['cardNumber']
				);
		}
		if ( isset( $post_data_for_log['unlimit_custom']['cvc'] ) ) {
			$post_data_for_log['unlimit_custom']['cvc'] = WC_Unlimit_Constants::SECURITY_CODE_MASKED;
		}
	}

	/**
	 * @return string
	 */
	public static function get_id() {
		return self::GATEWAY_ID;
	}
}