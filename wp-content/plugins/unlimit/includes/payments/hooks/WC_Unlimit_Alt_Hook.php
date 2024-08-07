<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../../module/config/WC_Unlimit_Constants.php';

class WC_Unlimit_Alt_Hook extends WC_Unlimit_Hook_Abstract {

	const ASSETS_IMAGES = '../../assets/images/';

	public function load_hooks() {
		parent::load_hooks();

		if ( $this->is_gateway_enabled() ) {
			add_action( 'woocommerce_thankyou_' . $this->gateway->id, [ $this, 'redirect_to_api_url' ] );
		}
	}

	protected function is_gateway_enabled() {
		return ( ! empty( $this->gateway->settings['enabled'] ) && 'yes' === $this->gateway->settings['enabled'] );
	}

	public function add_checkout_scripts( $gateway_postfix ) {
		if ( is_checkout() && $this->gateway->is_available() && ! get_query_var( 'order-received' ) ) {
			$handle = "unlimit-$gateway_postfix-checkout";

			wp_enqueue_script(
				$handle,
				plugins_url( "../../assets/js/$gateway_postfix.js", plugin_dir_path( __FILE__ ) ),
				[ 'jquery' ],
				WC_Unlimit_Constants::VERSION,
				true
			);

			wp_localize_script(
				$handle,
				"wc_unlimit_${gateway_postfix}_params",
				[
					'payer_email' => esc_js( $this->gateway->logged_user_email ),
					'apply'       => __( 'Apply', 'unlimit' ),
					'remove'      => __( 'Remove', 'unlimit' ),
					'ajax_url'    => admin_url('admin-ajax.php'),
					'ajax_nonce'  => wp_create_nonce('unlimitnonce'),
					'choose'      => __( 'To choose', 'unlimit' ),
					'other_bank'  => __( 'Other bank', 'unlimit' ),
					'loading'     => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
				]
			);
		}
	}
}
