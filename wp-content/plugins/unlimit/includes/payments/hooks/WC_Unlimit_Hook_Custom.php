<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/WC_Unlimit_Constants.php';

class WC_Unlimit_Hook_Custom extends WC_Unlimit_Hook_Abstract {

	const ASSETS_IMAGES = '../../assets/images/';

	public function load_hooks() {
		parent::load_hooks();

		if ( ! empty( $this->gateway->settings['enabled'] ) && 'yes' === $this->gateway->settings['enabled'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_custom' ] );
			add_action( 'woocommerce_thankyou_' . $this->gateway->id, [ $this, 'redirect_to_api_url' ] );
		}
	}

	public function add_checkout_scripts_custom() {
		if ( is_checkout() && $this->gateway->is_available() && ( ! get_query_var( 'order-received' ) ) ) {
			wp_enqueue_script(
				'unlimit-custom-checkout',
				plugins_url( '../../assets/js/credit-card.js', plugin_dir_path( __FILE__ ) ),
				[ 'jquery' ],
				WC_Unlimit_Constants::VERSION,
				true
			);

			wp_localize_script(
				'unlimit-custom-checkout',
				'wc_unlimit_custom_params',
				[
					'public_key'     => $this->gateway->get_public_key(),
					'installments'   => $this->gateway->get_option_ul( '_ul_installments' ),
					'payer_email'    => esc_js( $this->gateway->logged_user_email ),
					'apply'          => __( 'Apply', 'unlimit' ),
					'remove'         => __( 'Remove', 'unlimit' ),
					'ajax_url'       => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'     => wp_create_nonce( 'unlimitnonce' ),
					'choose'         => __( 'To choose', 'unlimit' ),
					'other_bank'     => __( 'Other bank', 'unlimit' ),
					'loading'        => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'          => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'          => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
					'plugin_version' => WC_Unlimit_Constants::VERSION,
				]
			);
		}
	}

	public static function delete_card() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'unlimitnonce' ) ) {
			wp_die();
		}

		$customer_id          = get_current_user_id();
		$fieldname_prefix     = WC_Unlimit_Admin_BankCard_Fields::FIELDNAME_PREFIX;
		$is_recurring_enabled = (
			'yes' === get_option(
				$fieldname_prefix .
				WC_Unlimit_Admin_BankCard_Fields::FIELD_RECURRING_ENABLED
			)
		);

		if ( is_user_logged_in() && $is_recurring_enabled && $customer_id ) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'ul_recurring_data';

			if ( $wpdb->delete(
				$table_name,
				[
					'recurring_data_id' => $_POST['recurring_data_id'],
					'customer_id'       => (int) $customer_id,
				]
			) ) {
				self::displaySuccess();
			}
		}
		self::displayError();
	}

	public static function displayError() {
		wp_die( json_encode( [
			'success' => false,
			'message' => __( "You're not authorized to perform this action.", "unlimit" )
		] ) );
	}

	public static function displaySuccess() {
		wp_die( json_encode( [
			'success' => true,
			'message' => __( "Your card has been deleted.", "unlimit" )
		] ) );
	}
}
