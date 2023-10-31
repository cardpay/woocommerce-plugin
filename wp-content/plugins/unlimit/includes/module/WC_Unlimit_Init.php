<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../payments/WC_Unlimit_Auth_Payment.php';
require_once __DIR__ . '/../payments/validator/WC_Unlimit_General_Validator.php';
require_once __DIR__ . '/../payments/validator/WC_Unlimit_Custom_Validator.php';
require_once __DIR__ . '/../payments/validator/WC_Unlimit_Boleto_Validator.php';
require_once __DIR__ . '/../payments/validator/WC_Unlimit_Pix_Validator.php';

class WC_Unlimit_Init {

	public static function unlimit_load_plugin_textdomain() {
		$text_domain = 'unlimit';
		$locale      = substr( apply_filters( 'plugin_locale', get_locale(), $text_domain ), 0, 2 );

		$original_language_file = __DIR__ . "/../../i18n/languages/unlimit-$locale.mo";
		if ( ! file_exists( $original_language_file ) ) {
			return;
		}

		// Unload the translation for the text domain of the plugin.
		unload_textdomain( $text_domain );

		// Load first the override file.
		load_textdomain( $text_domain, $original_language_file );
	}

	public static function unlimit_unsupported_php_version_notice() {
		$type    = 'error';
		$message = esc_html__(
			'Unlimit payments for WooCommerce requires PHP version 5.6 or later. Please update your PHP version.',
			'unlimit'
		);

		echo WC_Unlimit_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * Curl validation
	 */
	public static function unlimit_notify_curl_error() {
		$type    = 'error';
		$message = __( 'PHP Extension CURL is not installed.', 'unlimit' );

		echo WC_Unlimit_Notices::get_alert_frame( $message, $type );
	}

	/**
	 * Add mp order meta box actions function
	 *
	 * @param array $actions actions.
	 *
	 * @return array
	 */
	public static function add_ul_order_meta_box_actions( $actions ) {
		$actions['cancel_order'] = __( 'Cancel order', 'unlimit' );

		return $actions;
	}

	public static function ul_show_admin_notices() {
		if ( ! WC_Unlimit_Module::is_wc_new_version()
		     || ( ( isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] )
		          && is_plugin_active( 'woocommerce-admin/woocommerce-admin.php' ) ) ) {
			return;
		}

		$notices_array = WC_Unlimit_Module::$notices;
		$notices       = array_unique( $notices_array );
		foreach ( $notices as $notice ) {
			echo $notice;
		}
	}

	/**
	 * Activation plugin hook
	 */
	public static function unlimit_plugin_activation() {
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
		if ( version_compare( WC_Unlimit_Constants::VERSION, $old_version, '>' ) ) {
			update_option( '_ul_version', WC_Unlimit_Constants::VERSION, true );
		}
	}

	/**
	 * Init the plugin
	 */
	public static function unlimit_init() {
		self::unlimit_load_plugin_textdomain();

		require_once __DIR__ . '/config/WC_Unlimit_Constants.php';
		require_once __DIR__ . '/../admin/notices/WC_Unlimit_Notices.php';
		WC_Unlimit_Notices::init_unlimit_notice();

		// Check for PHP version and throw notice.
		if ( PHP_VERSION_ID <= 50600 ) {
			add_action( 'admin_notices', [ __CLASS__, 'unlimit_unsupported_php_version_notice' ] );

			return;
		}

		if ( ! in_array( 'curl', get_loaded_extensions(), true ) ) {
			add_action( 'admin_notices', [ __CLASS__, 'unlimit_notify_curl_error' ] );

			return;
		}

		// Load Unlimit SDK.
		require_once __DIR__ . '/sdk/lib/WC_Unlimit_Sdk.php';

		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once __DIR__ . '/WC_Unlimit_Exception.php';
			require_once __DIR__ . '/WC_Unlimit_Configs.php';
			require_once __DIR__ . '/log/WC_Unlimit_Logger.php';
			require_once __DIR__ . '/WC_Unlimit_Module.php';
			require_once __DIR__ . '/WC_Unlimit_Credentials.php';
			require_once __DIR__ . '/../admin/notices/WC_Unlimit_Review_Notice.php';

			WC_Unlimit_Module::init_unlimit_class();
			WC_Unlimit_Review_Notice::init_unlimit_review_notice();
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
		$prefix = WC_Unlimit_Admin_Order_Status_Fields::FIELDNAME_PREFIX;

		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::NEW_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::NEW_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::IN_PROCESS_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::IN_PROCESS_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::DECLINED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::DECLINED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::AUTHORIZED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::AUTHORIZED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::COMPLETED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::COMPLETED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::CANCELED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::CANCELED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::VOIDED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::VOIDED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::REFUNDED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::REFUNDED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::CHARGED_BACK_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::CHARGED_BACK_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::CHARGEBACK_RESOLVED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::CHARGEBACK_RESOLVED_WC_DEFAULT
		);
		add_option(
			$prefix .
			WC_Unlimit_Admin_Order_Status_Fields::TERMINATED_UNLIMIT,
			WC_Unlimit_Admin_Order_Status_Fields::TERMINATED_WC_DEFAULT
		);
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
		$auth_payment = new WC_Unlimit_Auth_Payment();
		wp_die( json_encode( $auth_payment->$action(), JSON_THROW_ON_ERROR ) );
	}

	public static function validate_form() {
		switch ( $_POST[ WC_Unlimit_Constants::PAYMENT_METHOD ] ) {
			case WC_Unlimit_Custom_Gateway::GATEWAY_ID:
				$validator = new WC_Unlimit_Custom_Validator();
				break;
			case WC_Unlimit_Ticket_Gateway::GATEWAY_ID:
				$validator = new WC_Unlimit_Boleto_Validator();
				break;
			case WC_Unlimit_Pix_Gateway::GATEWAY_ID:
				$validator = new WC_Unlimit_Pix_Validator();
				break;
			default:
				$validator = new WC_Unlimit_General_Validator();
		}

		$validator->validate();
	}
}
