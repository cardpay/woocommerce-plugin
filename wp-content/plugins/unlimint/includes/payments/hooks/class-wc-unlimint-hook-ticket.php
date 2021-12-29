<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/class-wc-unlimint-constants.php';

class WC_Unlimint_Hook_Ticket extends WC_Unlimint_Hook_Abstract {

	const ASSETS_IMAGES = '../../assets/images/';

	public function load_hooks() {
		parent::load_hooks();

		if ( ! empty( $this->payment->settings['enabled'] ) && 'yes' === $this->payment->settings['enabled'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_ticket' ] );
			add_action( 'woocommerce_thankyou_' . $this->payment->id, [ $this, 'redirect_to_api_url' ] );
		}
	}

	public function add_checkout_scripts_ticket() {
		if ( is_checkout() && $this->payment->is_available() && ! get_query_var( 'order-received' ) ) {
			wp_enqueue_script(
				'unlimint-ticket-checkout',
				plugins_url( '../../assets/js/ticket.js', plugin_dir_path( __FILE__ ) ),
				[ 'jquery' ],
				WC_Unlimint_Constants::VERSION,
				true
			);

			wp_localize_script(
				'unlimint-ticket-checkout',
				'wc_unlimint_ticket_params',
				[
					'payer_email'         => esc_js( $this->payment->logged_user_email ),
					'apply'               => __( 'Apply', 'unlimint' ),
					'remove'              => __( 'Remove', 'unlimint' ),
					'choose'              => __( 'To choose', 'unlimint' ),
					'other_bank'          => __( 'Other bank', 'unlimint' ),
					'loading'             => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'               => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'               => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
				]
			);
		}
	}
}