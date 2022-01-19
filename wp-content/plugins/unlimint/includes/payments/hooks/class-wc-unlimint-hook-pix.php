<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/class-wc-unlimint-constants.php';

class WC_Unlimint_Hook_Pix extends WC_Unlimint_Hook_Abstract {

	const ASSETS_IMAGES = '../../assets/images/';

	public function load_hooks() {
		parent::load_hooks();

		if ( ! empty( $this->gateway->settings['enabled'] ) && 'yes' === $this->gateway->settings['enabled'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_pix' ] );
			add_action( 'woocommerce_thankyou_' . $this->gateway->id, [ $this, 'redirect_to_api_url' ] );
		}
	}

	public function add_checkout_scripts_pix() {
		if ( is_checkout() && $this->gateway->is_available() && ! get_query_var( 'order-received' ) ) {
			wp_enqueue_script(
				'unlimint-pix-checkout',
				plugins_url( '../../assets/js/pix.js', plugin_dir_path( __FILE__ ) ),
				[ 'jquery' ],
				WC_Unlimint_Constants::VERSION,
				true
			);

			wp_localize_script(
				'unlimint-pix-checkout',
				'wc_unlimint_pix_params',
				[
					'payer_email' => esc_js( $this->gateway->logged_user_email ),
					'apply'       => __( 'Apply', 'unlimint' ),
					'remove'      => __( 'Remove', 'unlimint' ),
					'choose'      => __( 'To choose', 'unlimint' ),
					'other_bank'  => __( 'Other bank', 'unlimint' ),
					'loading'     => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
				]
			);
		}
	}
}