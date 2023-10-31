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
}
