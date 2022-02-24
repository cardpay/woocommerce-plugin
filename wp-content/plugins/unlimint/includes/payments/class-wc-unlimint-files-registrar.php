<?php

class WC_Unlimint_Files_Registrar {
	public function register_common_settings_js() {
		$this->register_settings_js( 'common', 'common_settings_unlimint.js' );
	}

	public function register_settings_js( $handler_id, $file ) {
		if ( ! is_admin() || empty( $handler_id ) || empty( $file ) ) {
			return;
		}

		wp_enqueue_script(
			"unlimint-$handler_id-config-script",
			plugins_url( "../assets/js/admin_settings/$file", plugin_dir_path( __FILE__ ) ),
			[],
			WC_Unlimint_Constants::VERSION
		);
	}

	public function register_css() {
		wp_enqueue_style(
			'unlimint-basic-checkout-styles',
			plugins_url( '../assets/css/basic_checkout_unlimint.css', plugin_dir_path( __FILE__ ) ),
			[],
			WC_Unlimint_Constants::VERSION
		);
	}
}