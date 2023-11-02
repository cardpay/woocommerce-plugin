<?php

class WC_Unlimit_Files_Registrar {
	public function register_common_settings_js() {
		$this->register_settings_js( 'common', 'common_settings_unlimit.js' );
	}

	public function register_settings_js( $handler_id, $file ) {
		if ( ! is_admin() || empty( $handler_id ) || empty( $file ) ) {
			return;
		}

		$file_path = plugin_dir_path( __FILE__ ) . "../../assets/js/admin_settings/$file";

		if ( file_exists( $file_path ) ) {
			wp_enqueue_script(
				"unlimit-$handler_id-config-script",
				plugins_url( "../assets/js/admin_settings/$file", plugin_dir_path( __FILE__ ) ),
				[],
				WC_Unlimit_Constants::VERSION
			);
		}
	}

	public function register_css() {
		wp_enqueue_style(
			'unlimit-basic-checkout-styles',
			plugins_url( '../assets/css/basic_checkout_unlimit.css', plugin_dir_path( __FILE__ ) ),
			[],
			WC_Unlimit_Constants::VERSION
		);
	}

	public function load_payment_form_script() {
		$this->register_settings_js( 'payment-form', 'payment_form_setup.js' );
	}
}