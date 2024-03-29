<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Notices {

	/**
	 * Static Instance
	 *
	 * @var WC_Unlimit_Notices
	 */
	public static $instance;


	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_notice_css' ] );
	}

	/**
	 * Initialize
	 *
	 * @return WC_Unlimit_Notices|null
	 * Singleton
	 */
	public static function init_unlimit_notice() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load admin notices CSS
	 */
	public function load_admin_notice_css() {
		if ( is_admin() ) {
			wp_enqueue_style(
				'unlimit-admin-notice',
				plugins_url( '../../assets/css/admin_notice_unlimit.css', plugin_dir_path( __FILE__ ) ),
				[],
				WC_Unlimit_Constants::VERSION
			);
		}
	}

	/**
	 * Alert frame
	 *
	 * @param string $message message.
	 * @param string $type type.
	 */
	public static function get_alert_frame( $message, $type ) {
		$inline = '';
		if ( ( class_exists( 'WC_Unlimit_Module' ) && WC_Unlimit_Module::is_wc_new_version() )
		     && ( isset( $_GET['page'] ) && 'wc-settings' === sanitize_key( $_GET['page'] ) ) ) {
			$inline = 'inline';
		}

		$notice = '<div id="message" class="notice ' . $type . ' is-dismissible ' . $inline . '">
                    <div class="ul-alert-frame">
                        <div class="ul-left-alert">
                            <img src="' . plugins_url(
				'../../assets/images/minilogo.png',
				plugin_dir_path( __FILE__ )
			) . '">
                        </div>
                        <div class="ul-right-alert">
                            <p>' . $message . '</p>
                        </div>
                    </div>
                </div>';

		if ( class_exists( 'WC_Unlimit_Module' ) ) {
			WC_Unlimit_Module::$notices[] = $notice;
		}

		return $notice;
	}
}
