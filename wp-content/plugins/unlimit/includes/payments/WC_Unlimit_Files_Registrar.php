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

			$bankcard_translations = [
				'are_you_sure'                  => __( 'Are you sure you want to', 'unlimit' ),
				'the_payment'                   => __( 'The payment?', 'unlimit' ),
				'payment_was_not'               => __( 'Payment was not', 'unlimit' ),
				'payment_has_been'              => __( 'Payment has been', 'unlimit' ),
				'successfully'                  => __( 'Successfully', 'unlimit' ),
				'cancel'                        => __( 'Cancel', 'unlimit' ),
				'capture'                       => __( 'Capture', 'unlimit' ),
				'cancelled'                     => __( 'Cancelled', 'unlimit' ),
				'captured'                      => __( 'Captured', 'unlimit' ),
				'merchant_financed_translation' => __( 'Merchant financed', 'unlimit' ),
				'api_mode_change_warning'       =>
					__( 'API access mode is changed, please check Terminal code, Terminal password, Callback secret values.',
						'unlimit' ) .
					__( 'After changing of the API mode in plugin also must be changed API access mode in Unlimit.',
						'unlimit' ) . ' ' .
					__( 'Please consult about it with Unlimit support.', 'unlimit' ),
			];

			wp_localize_script( "unlimit-$handler_id-config-script", 'unlimit_vars', [
				'bankcard_translations' => $bankcard_translations,
			] );
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

	public function load_order_actions() {
		$this->register_settings_js( 'order-actions', 'order_actions.js' );
	}
}