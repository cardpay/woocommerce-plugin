<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-wc-unlimint-admin-fields.php';

class WC_Unlimint_Admin_BankCard_Fields extends WC_Unlimint_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimint_bankcard_';
	public const FIELD_CAPTURE_PAYMENT = 'capture_payment';
	public const FIELD_API_ACCESS_MODE = 'payment_page';
	public const FIELD_INSTALLMENT_ENABLED = 'installment_enabled';
	public const FIELD_INSTALLMENT_TYPE = 'installment_type';
	public const FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS = 'maximum_accepted_installments';
	public const FIELD_MINIMUM_TOTAL_AMOUNT = 'minimum_total_amount';
	public const FIELD_ASK_CPF = 'ask_cpf';
	public const FIELD_DYNAMIC_DESCRIPTOR = 'dynamic_descriptor';

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$form_fields                                                                               = [];
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_API_ACCESS_MODE ]                       = $this->field_api_access_mode();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_CODE ]     = $this->field_terminal_code();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TERMINAL_PASSWORD ] = $this->field_terminal_password();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_CALLBACK_SECRET ]   = $this->field_callback_secret();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  = $this->field_test_environment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_CAPTURE_PAYMENT ]                       = $this->field_capture_payment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_ENABLED ]                   = $this->field_installment_enabled();

		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ]     = $this->field_installment_type();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MINIMUM_TOTAL_AMOUNT ] = $this->field_minimum_total_amount();

		if ( 'IF' === get_option( self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ) ) {
			$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS ] = $this->field_maximum_accepted_installments_issuer();
		} else {
			$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS ] = $this->field_maximum_accepted_installments_merhcant();
		}
		if ( 'payment_page' === get_option( self::FIELDNAME_PREFIX . self::FIELD_API_ACCESS_MODE ) ) {
			$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ]['options'] = [ 'IF' => __( 'Issuer financed', 'unlimint' ), ];
		}

		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_PAYMENT_TITLE ] = $this->field_payment_title();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_ASK_CPF ]                           = $this->field_ask_cpf();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_DYNAMIC_DESCRIPTOR ]                = $this->field_dynamic_descriptor();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimint_Admin_Fields::FIELD_LOG_TO_FILE ]   = $this->field_log_to_file();

		return $form_fields;
	}

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( 'Credit Card - Unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_capture_payment() {
		return [
			'title'       => __( 'Capture Payment', 'unlimint' ),
			'type'        => 'select',
			'description' => __( 'If set to "No", the amount will not be captured but only blocked. With "No" option selected payments will be captured automatically in 7 days from the time of creating the preauthorized transaction.', 'unlimint' ) . "<br>" . __( 'In installment case with "No" option selected installments will be declined automatically in 7 days from the time of creating the preauthorized transaction.', 'unlimint' ),
			'default'     => 'yes',
			'options'     => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_installment_enabled() {
		return [
			'title'       => __( 'Installment Enabled', 'unlimint' ),
			'type'        => 'select',
			'description' => 'If set to Yes then installment payment field will be presented on payment form and installment payments can be possible for processing',
			'default'     => 'no',
			'options'     => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_installment_type() {
		$nameIF = '<a href="https://integration.unlimint.com/#Issuer-financed-(IF)">' . __( 'Issuer financed installments', 'unlimint' ) . '</a>';
		$nameMF = '<a href="https://integration.unlimint.com/#Merchant-financed-(MF_HOLD)">' . __( 'MF HOLD Installments', 'unlimint' ) . '</a>';

		return [
			'title'       => __( 'Installment Type', 'unlimint' ),
			'type'        => 'select',
			'description' => __( 'Should be selected only if "Installment enabled" setting is switched on. Here can be choosed Installment type used in trade plugin.', 'unlimint' ) . "<br>" . __( 'More details about installment types you can read', 'unlimint' ) . ' ' . '<a href="https://integration.unlimint.com/#Issuer-financed-(IF)">' . $nameIF . ', ' . $nameMF,
			'default'     => 'no',
			'options'     => [
				'IF'      => __( 'Issuer financed', 'unlimint' ),
				'MF_HOLD' => __( 'Merchant financed', 'unlimint' ),
			]
		];
	}

	/**
	 * @return array
	 */
	public function field_minimum_total_amount() {
		return [
			'title'             => __( 'Minimum total amount', 'unlimint' ) . ', ' . get_woocommerce_currency_symbol(),
			'type'              => 'number',
			'description'       => __( 'Total amount of order with installments, should be more than value of this setting.', 'unlimint' ),
			'default'           => 0,
			'custom_attributes' => [
				'step' => 'any',
				'min'  => '0'
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_maximum_accepted_installments_merhcant() {
		return [
			'title'       => __( 'Maximum accepted installments', 'unlimint' ),
			'type'        => 'select',
			'description' => $this->getMaximumAcceptedInstallmentsDescription(),
			'default'     => '1',
			'options'     => [
				'1'  => 1,
				'2'  => 2,
				'3'  => 3,
				'4'  => 4,
				'5'  => 5,
				'6'  => 6,
				'7'  => 7,
				'8'  => 8,
				'9'  => 9,
				'10' => 10,
				'11' => 11,
				'12' => 12,
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_maximum_accepted_installments_issuer() {
		return [
			'title'       => __( 'Maximum accepted installments', 'unlimint' ),
			'type'        => 'select',
			'description' => $this->getMaximumAcceptedInstallmentsDescription(),
			'default'     => '3',
			'options'     => [
				'3'  => 3,
				'6'  => 6,
				'9'  => 9,
				'12' => 12,
				'18' => 18,
			],
		];
	}

	protected function getMaximumAcceptedInstallmentsDescription() {
		return __(
			       'Maximum accepted intallments,', 'unlimint' )
		       . '<br>'
		       . __( 'For "Merchant Financed" installments can be single value from interval 1-12, for example 2, 3, 6, 8, 12.', 'unlimint' )
		       . '<br>'
		       . __( 'For "Issuer financed" Installment type valid values are 3, 6, 9, 12, 18.', 'unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( 'Credit Card - Unlimint' );
	}

	/**
	 * @return array
	 */
	public function field_ask_cpf() {
		return [
			'title'   => __( 'Ask CPF', 'unlimint' ),
			'type'    => 'select',
			'default' => 'no',
			'options' => [
				'no'  => __( 'No', 'unlimint' ),
				'yes' => __( 'Yes', 'unlimint' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_dynamic_descriptor() {
		return [
			'title'   => __( 'Dynamic Descriptor', 'unlimint' ),
			'type'    => 'text',
			'default' => '',
		];
	}

	public function field_api_access_mode() {
		return [
			'title'       => __( 'API access mode', 'unlimint' ),
			'type'        => 'select',
			'description' => __(
				                 'If "Payment page" mode is selected - payment page by Unlimint in iFrame is used for customer data collecting.', 'unlimint' )
			                 . '<br>'
			                 . __( 'If "Gateway" mode is selected - embedded payment form in plugin is used for customer data collecting.', 'unlimint' ),
			'default'     => 'Payment page',
			'options'     => [
				'payment_page' => __( 'Payment page', 'unlimint' ),
				'gateway'      => __( 'Gateway', 'unlimint' ),
			],
			'onkeyup'     => "formatUlCardField(this.id);",
		];
	}
}