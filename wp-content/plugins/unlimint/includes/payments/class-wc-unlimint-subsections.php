<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Subsections {
	public const SETTINGS_SECTION_LABEL = 'Settings';
	public const ORDER_STATUS_TAB_LABEL = 'Order Status';
	public const SUBSECTION_GET_PARAM = 'subsection';
	public const SUBSECTION_ID = 'order-status';

	/**
	 * @var boolean
	 */
	private $is_form_saved = false;

	public function form_is_saved() {
		$this->is_form_saved = true;
	}

	public function show_subsections_navigation() {
		if ( $this->is_form_saved || ! is_admin() || empty( $_GET['section'] ) ) {
			return;
		}

		if ( ! empty( $_GET[ self::SUBSECTION_GET_PARAM ] ) && ( self::SUBSECTION_ID === $_GET[ self::SUBSECTION_GET_PARAM ] ) ) {
			$section_params = 'section=' . $_GET['section'];
			$settings_url   = admin_url( "admin.php?page=wc-settings&tab=checkout&$section_params" );
			echo "<a href='$settings_url'>" . self::SETTINGS_SECTION_LABEL . "</a> | " . self::ORDER_STATUS_TAB_LABEL;
		} else {
			$section_params = 'section=' . $_GET['section'] . '&' . self::SUBSECTION_GET_PARAM . '=' . self::SUBSECTION_ID;
			$settings_url   = admin_url( "admin.php?page=wc-settings&tab=checkout&$section_params" );
			echo self::SETTINGS_SECTION_LABEL . " | <a href='$settings_url'>" . self::ORDER_STATUS_TAB_LABEL . "</a>";
		}
	}
}