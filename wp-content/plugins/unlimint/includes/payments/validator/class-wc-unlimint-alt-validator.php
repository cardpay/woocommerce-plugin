<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-general-validator.php';

class WC_Unlimint_Alt_Validator extends WC_Unlimint_General_Validator {
	private const VALIDATION_RULES = [
		'first_name' => [ 'First Name', 256 ],
		'last_name'  => [ 'Last Name', 256 ],
	];

	public function validate() {
		$this->set_validation_rules( self::VALIDATION_RULES );

		parent::validate();
	}
}