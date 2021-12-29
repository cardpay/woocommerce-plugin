<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/class-wc-unlimint-constants.php';

class WC_Unlimint_Hook_Custom extends WC_Unlimint_Hook_Abstract {

	const ASSETS_IMAGES = '../../assets/images/';

	public function load_hooks() {
		parent::load_hooks();

		if ( ! empty( $this->payment->settings['enabled'] ) && 'yes' === $this->payment->settings['enabled'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_custom' ] );
			add_action( 'woocommerce_thankyou_' . $this->payment->id, [ $this, 'redirect_to_api_url' ] );
		}
	}

	public function add_checkout_scripts_custom() {
		if ( is_checkout() && $this->payment->is_available() && ! get_query_var( 'order-received' ) ) {
			wp_enqueue_script(
				'unlimint-custom-checkout',
				plugins_url( '../../assets/js/credit-card.js', plugin_dir_path( __FILE__ ) ),
				[ 'jquery' ],
				WC_Unlimint_Constants::VERSION,
				true
			);

			wp_localize_script(
				'unlimint-custom-checkout',
				'wc_unlimint_custom_params',
				[
					'public_key'          => $this->payment->get_public_key(),
					'installments'        => $this->payment->get_option_ul( '_ul_installments' ),
					'payer_email'         => esc_js( $this->payment->logged_user_email ),
					'apply'               => __( 'Apply', 'unlimint' ),
					'remove'              => __( 'Remove', 'unlimint' ),
					'choose'              => __( 'To choose', 'unlimint' ),
					'other_bank'          => __( 'Other bank', 'unlimint' ),
					'loading'             => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'               => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'               => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
					'plugin_version'      => WC_Unlimint_Constants::VERSION,
				]
			);
		}
	}
}