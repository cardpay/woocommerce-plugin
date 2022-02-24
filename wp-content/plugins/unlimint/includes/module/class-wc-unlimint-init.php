<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../payments/class-wc-unlimint-auth-payment.php';
require_once __DIR__ . '/../payments/validator/class-wc-unlimint-alt-validator.php';
require_once __DIR__ . '/../payments/validator/class-wc-unlimint-custom-validator.php';

class WC_Unlimint_Init {
	public const GATEWAY_ALT = [
		WC_Unlimint_Pix_Gateway::GATEWAY_ID,
		WC_Unlimint_Ticket_Gateway::GATEWAY_ID
	];

	public static function unlimint_load_plugin_textdomain() {
		$text_domain = 'unlimint';
		$locale      = substr( apply_filters( 'plugin_locale', get_locale(), $text_domain ), 0, 2 );

		$original_language_file = __DIR__ . "/../../i18n/languages/unlimint-$locale.mo";
		if ( ! file_exists( $original_language_file ) ) {
			return;
		}

		// Unload the translation for the text domain of the plugin.
		unload_textdomain( $text_domain );

		// Load first the override file.
		load_textdomain( $text_domain, $original_language_file );
	}

	public static function wc_unlimint_unsupported_php_version_notice() {
		$type    = 'error';
		$message = esc_html__( 'Unlimint payments for WooCommerce requires PHP version 5.6 or later. Please update your PHP version.', 'unlimint' );

		echo WC_Unlimint_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * Curl validation
	 */
	public static function wc_unlimint_notify_curl_error() {
		$type    = 'error';
		$message = __( 'PHP Extension CURL is not installed.', 'unlimint' );

		echo WC_Unlimint_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * Add mp order meta box actions function
	 *
	 * @param array $actions actions.
	 *
	 * @return array
	 */
	public static function add_ul_order_meta_box_actions( $actions ) {
		$actions['cancel_order'] = __( 'Cancel order', 'unlimint' );

		return $actions;
	}

	public static function ul_show_admin_notices() {
		if ( ! WC_Unlimint_Module::is_wc_new_version()
		     || ( ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] )
		          && is_plugin_active( 'woocommerce-admin/woocommerce-admin.php' ) ) ) {
			return;
		}

		$notices_array = WC_Unlimint_Module::$notices;
		$notices       = array_unique( $notices_array, SORT_STRING );
		foreach ( $notices as $notice ) {
			echo $notice;
		}
	}

	/**
	 * Activation plugin hook
	 */
	public static function unlimint_plugin_activation() {
		$dismissed_review = (int) get_option( '_ul_dismiss_review' );
		if ( ! isset( $dismissed_review ) || 1 === $dismissed_review ) {
			update_option( '_ul_dismiss_review', 0, true );
		}
	}

	/**
	 * Update plugin version in db
	 */
	public static function update_plugin_version() {
		$old_version = get_option( '_ul_version', '0' );
		if ( version_compare( WC_Unlimint_Constants::VERSION, $old_version, '>' ) ) {
			update_option( '_ul_version', WC_Unlimint_Constants::VERSION, true );
		}
	}

	/**
	 * Init the plugin
	 */
	public static function unlimint_init() {
		self::unlimint_load_plugin_textdomain();

		require_once __DIR__ . '/config/class-wc-unlimint-constants.php';
		require_once __DIR__ . '/../admin/notices/class-wc-unlimint-notices.php';
		WC_Unlimint_Notices::init_unlimint_notice();

		// Check for PHP version and throw notice.
		if ( PHP_VERSION_ID <= 50600 ) {
			add_action( 'admin_notices', [ __CLASS__, 'wc_unlimint_unsupported_php_version_notice' ] );

			return;
		}

		if ( ! in_array( 'curl', get_loaded_extensions(), true ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'wc_unlimint_notify_curl_error' ] );

			return;
		}

		// Load Unlimint SDK.
		require_once __DIR__ . '/sdk/lib/class-unlimint-sdk.php';

		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once __DIR__ . '/class-wc-unlimint-exception.php';
			require_once __DIR__ . '/class-wc-unlimint-configs.php';
			require_once __DIR__ . '/log/class-wc-unlimint-logger.php';
			require_once __DIR__ . '/class-wc-unlimint-module.php';
			require_once __DIR__ . '/class-wc-unlimint-credentials.php';
			require_once __DIR__ . '/../admin/notices/class-wc-unlimint-review-notice.php';

			WC_Unlimint_Module::init_unlimint_class();
			WC_Unlimint_Review_Notice::init_unlimint_review_notice();
			self::update_plugin_version();

			add_action( 'woocommerce_order_actions', [ __CLASS__, 'add_ul_order_meta_box_actions' ] );
			self::save_default_order_statuses_mapping();
		}

		add_action( 'woocommerce_settings_checkout', [ __CLASS__, 'ul_show_admin_notices' ] );

		add_action( 'woocommerce_checkout_process', [ __CLASS__, 'validate_form' ] );

		add_action( 'wp_ajax_wc_ul_capture', [ __CLASS__, 'ajax_ul_capture_payment' ] );
		add_action( 'wp_ajax_wc_ul_cancel', [ __CLASS__, 'ajax_ul_cancel_payment' ] );
	}

	private static function save_default_order_statuses_mapping() {
		$prefix = WC_Unlimint_Admin_Order_Status_Fields::FIELDNAME_PREFIX;

		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::NEW_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::NEW_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::IN_PROCESS_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::IN_PROCESS_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::DECLINED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::DECLINED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::AUTHORIZED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::AUTHORIZED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::COMPLETED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::COMPLETED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::CANCELED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::CANCELED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::VOIDED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::VOIDED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::REFUNDED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::REFUNDED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::CHARGED_BACK_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::CHARGED_BACK_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::CHARGEBACK_RESOLVED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::CHARGEBACK_RESOLVED_WC_DEFAULT );
		add_option( $prefix . WC_Unlimint_Admin_Order_Status_Fields::TERMINATED_UNLIMINT, WC_Unlimint_Admin_Order_Status_Fields::TERMINATED_WC_DEFAULT );
	}

	/**
	 * @throws JsonException
	 */
	public static function ajax_ul_capture_payment() {
		self::do_payment_action( 'ajax_capture' );
	}

	/**
	 * @throws JsonException
	 */
	public static function ajax_ul_cancel_payment() {
		self::do_payment_action( 'ajax_cancel' );
	}

	private static function do_payment_action( $action ) {
		$auth_payment = new WC_Unlimint_Auth_Payment();
		wp_die( json_encode( $auth_payment->$action(), JSON_THROW_ON_ERROR ) );
	}

	public static function validate_form() {
		if ( empty( $_POST[ WC_Unlimint_Constants::PAYMENT_METHOD ] ) ) {
			return;
		}

		if ( $_POST[ WC_Unlimint_Constants::PAYMENT_METHOD ] === WC_Unlimint_Custom_Gateway::GATEWAY_ID ) {
			$card_validator = new WC_Unlimint_Custom_Validator();
			$card_validator->validate();

			return;
		}

		if ( in_array( $_POST[ WC_Unlimint_Constants::PAYMENT_METHOD ], self::GATEWAY_ALT ) ) {
			$alt_validator = new WC_Unlimint_Alt_Validator();
			$alt_validator->validate();
		}
	}
}