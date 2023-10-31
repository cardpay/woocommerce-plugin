<?php

require_once __DIR__ . '/class-wc-unlimint-general-validator.php';

class WC_Unlimint_Pix_Validator extends WC_Unlimint_General_Validator {
	private const VALIDATION_RULES = [
		'first_name' => [ 'First name', 256 ],
		'last_name'  => [ 'Last name', 256 ],
		'postcode'   => [ 'Postcode / ZIP', 17 ],
	];

	public function validate() {
		$this->set_validation_rules( self::VALIDATION_RULES );

		parent::validate();
	}
}