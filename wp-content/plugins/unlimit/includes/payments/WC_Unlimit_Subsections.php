<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Subsections {
	public const SUBSECTION_GET_PARAM = 'subsection';

	/**
	 * @var boolean
	 */
	private $is_form_saved = false;

	public function form_is_saved() {
		$this->is_form_saved = true;
	}
}