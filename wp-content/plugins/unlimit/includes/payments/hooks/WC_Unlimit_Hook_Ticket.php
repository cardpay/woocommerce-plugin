<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Alt_Hook.php';
require_once __DIR__ . '/../../module/config/WC_Unlimit_Constants.php';

class WC_Unlimit_Hook_Ticket extends WC_Unlimit_Alt_Hook {

	public function load_hooks() {
		parent::load_hooks();

		if ( $this->is_gateway_enabled() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_ticket' ] );
		}
	}

	public function add_checkout_scripts_ticket() {
		$this->add_checkout_scripts( 'ticket' );
	}
}