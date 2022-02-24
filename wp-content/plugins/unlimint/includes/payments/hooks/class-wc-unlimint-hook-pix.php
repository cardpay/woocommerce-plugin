<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-alt-hook.php';
require_once __DIR__ . '/../../module/config/class-wc-unlimint-constants.php';

class WC_Unlimint_Hook_Pix extends WC_Unlimint_Alt_Hook {

	public function load_hooks() {
		parent::load_hooks();

		if ( $this->is_gateway_enabled() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_checkout_scripts_pix' ] );
		}
	}

	public function add_checkout_scripts_pix() {
		$this->add_checkout_scripts( 'pix' );
	}
}