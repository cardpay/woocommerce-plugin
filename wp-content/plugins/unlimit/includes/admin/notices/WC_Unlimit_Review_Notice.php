<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Review_Notice {

	/**
	 * Static instance
	 *
	 * @var WC_Unlimit_Review_Notice
	 */
	public static $instance;

	/**
	 * WC_Unlimit_ReviewNotice constructor.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_notice_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_notice_js' ] );
		add_action( 'wp_ajax_unlimit_review_dismiss', [ $this, 'review_dismiss' ] );
	}

	/**
	 * Singleton
	 *
	 * @return WC_Unlimit_Review_Notice|null
	 */
	public static function init_unlimit_review_notice() {
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
	 * Load admin notices JS
	 */
	public function load_admin_notice_js() {
		if ( is_admin() ) {
			wp_enqueue_script(
				'unlimit-admin-notice-review',
				plugins_url( '../../assets/js/review.js', plugin_dir_path( __FILE__ ) ),
				[],
				WC_Unlimit_Constants::VERSION
			);
		}
	}

	/**
	 * Dismiss the review admin notice
	 */
	public function review_dismiss() {
		$dismissed_review = (int) get_option( '_ul_dismiss_review', 0 );

		if ( 0 === $dismissed_review ) {
			update_option( '_ul_dismiss_review', 1, true );
		}

		wp_send_json_success();
	}
}
