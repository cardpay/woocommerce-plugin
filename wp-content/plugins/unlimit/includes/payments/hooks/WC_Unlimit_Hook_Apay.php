<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Hook.php';
require_once __DIR__ . '/../../module/config/WC_Unlimit_Constants.php';

class WC_Unlimit_Hook_Apay extends WC_Unlimit_Alt_Hook {

    const KEY_UPLOAD_DIR = "/unlimit/assets/upload/";

	private const EMPTY_FILE_ERROR = [
		'woocommerce_unlimit_apay_merchant_certificate' => 'Empty payment certificate',
		'woocommerce_unlimit_apay_merchant_key' => 'Empty merchant certificate',
	];

	private const INVALID_FILE_ERROR = [
		'woocommerce_unlimit_apay_merchant_key' => 'Invalid merchant certificate file format',
		'woocommerce_unlimit_apay_merchant_certificate' => 'Invalid payment certificate file format',
	];
	public function load_hooks() {
		parent::load_hooks();

		if ( $this->is_gateway_enabled() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_apay' ] );
		}
	}

	public function add_checkout_scripts_apay() {
		$this->add_checkout_scripts( 'apay' );
	}

	/**
	 * @return bool
	 */
	public function custom_process_admin_options() {
		$this->gateway->init_settings();
		$post_data = $this->gateway->get_post_data();
        $sett = new WC_Admin_Settings();
		foreach ( $this->gateway->get_form_fields(false, $this->gateway->settings) as $key => $field ) {
			$value = $this->gateway->get_field_value( $key, $field, $post_data );
            if ($field['type'] === 'file') {
                $uploadedfile = $_FILES['woocommerce_woo-unlimit-apay_'.$key];
                $value = $this->gateway->settings[$key];
                if (!$this->validate_file($uploadedfile, $value, $key)) {
					continue;
                }
                $file_path = WP_PLUGIN_DIR.self::KEY_UPLOAD_DIR.$uploadedfile['name'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                if (!move_uploaded_file(
                        $uploadedfile['tmp_name'],
                        $file_path
                )) {
                    $sett->add_error(__( 'Error uploading file.', 'unlimit' ));
                    continue;
                }

                $value = $uploadedfile['name'];
            }
			update_option( $key, $value, true );
			$this->gateway->settings[ $key ] = $value;
		}

		return update_option( $this->gateway->get_option_key(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->gateway->id,
				$this->gateway->settings ) );
	}

	public function validate_file($file, $value, $key) {
        $sett = new WC_Admin_Settings();
        if ($file['error'] === 4) {
			if (empty($value)) {
	            $sett->add_error(__( self::EMPTY_FILE_ERROR[$key], 'unlimit' ));
	        }
            return false;
        }
        $mimeType = mime_content_type( $file['tmp_name']);
		$name = explode('.', $file['name']);
		$ext = end($name);
        if ($mimeType !== 'text/plain' || $ext !== 'pem') {
            $sett->add_error(__( self::INVALID_FILE_ERROR[$key], 'unlimit' ));
			return false;
        }
		return true;
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
					'store_name'  => get_bloginfo('name'),
					'currency'	  => get_woocommerce_currency(),
					'merchant_id' => $this->gateway->settings['apple_merchant_id'],
					'payer_email' => esc_js( $this->gateway->logged_user_email ),
					'apply'       => __( 'Apply', 'unlimit' ),
					'remove'      => __( 'Remove', 'unlimit' ),
					'choose'      => __( 'To choose', 'unlimit' ),
					'other_bank'  => __( 'Other bank', 'unlimit' ),
					'loading'     => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'loading.gif',
					'check'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'check.png',
					'error'       => plugins_url( self::ASSETS_IMAGES, plugin_dir_path( __FILE__ ) ) . 'error.png',
				]
			);
		}
	}

    public static function validate_merchant()
    {
		if (!wp_verify_nonce( $_POST['nonce'], 'unlimitnonce' )) {
			wp_die();
		}
        $postData = array(
			'merchantIdentifier' => $_POST['merchantIdentifier'],
			'displayName' => $_POST['displayName'],
			'domainName' => gethostname(),
			'initiative' => 'web',
			'initiativeContext' => gethostname()
		);
		$gateway = new WC_Unlimit_Apay_Gateway();
		$postDataFields = json_encode($postData);
		$url = $_POST['url'];
		$merchant_crt = $gateway->settings[WC_Unlimit_Admin_Apay_Fields::FIELDNAME_PREFIX.'merchant_certificate'];
		$merchant_key = $gateway->settings[WC_Unlimit_Admin_Apay_Fields::FIELDNAME_PREFIX.'merchant_key'];
		try {
			$curlOptions = array(
				CURLOPT_URL => $url ? $url : 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession',
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $postDataFields,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSLCERT => WP_PLUGIN_DIR.self::KEY_UPLOAD_DIR.$merchant_crt,
				CURLOPT_SSLKEY => WP_PLUGIN_DIR.self::KEY_UPLOAD_DIR.$merchant_key,
				CURLOPT_SSLCERTPASSWD => '',
				CURLOPT_SSLKEYTYPE => 'PEM',
				CURLOPT_SSL_VERIFYPEER => true
			);
			$curlConnection = curl_init();
			curl_setopt_array($curlConnection, $curlOptions);
			$response = curl_exec($curlConnection);
			print_r($response);
		} catch (\Exception $e) {
			$gateway->logger->error( __FUNCTION__, $e->getMessage() );
		}
        wp_die();
    }
}