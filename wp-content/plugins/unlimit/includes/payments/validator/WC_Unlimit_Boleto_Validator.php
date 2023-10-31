<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_General_Validator.php';

class WC_Unlimit_Boleto_Validator extends WC_Unlimit_General_Validator {
	private const VALIDATION_RULES = [
		'first_name' => [ 'First name', 256 ],
		'last_name'  => [ 'Last name', 256 ],
		'postcode'   => [ 'Postcode / ZIP', 8 ],
	];

	public function validate() {
		$this->set_validation_rules( self::VALIDATION_RULES );

		parent::validate();
	}
}