<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/WC_Unlimit_Admin_Fields.php';

class WC_Unlimit_Admin_BankCard_Fields extends WC_Unlimit_Admin_Fields {

	public const FIELDNAME_PREFIX = 'woocommerce_unlimit_bankcard_';
	public const FIELD_CAPTURE_PAYMENT = 'capture_payment';
	public const FIELD_API_ACCESS_MODE = 'payment_page';
	public const FIELD_INSTALLMENT_ENABLED = 'installment_enabled';
	public const FIELD_INSTALLMENT_TYPE = 'installment_type';
	public const FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS = 'maximum_accepted_installments';
	public const FIELD_MINIMUM_INSTALLMENT_AMOUNT = 'minimum_installment_amount';
	public const FIELD_ASK_CPF = 'ask_cpf';
	public const FIELD_DYNAMIC_DESCRIPTOR = 'dynamic_descriptor';

	/**
	 * @return array
	 */
	public function get_form_fields() {
		$form_fields                                                                              = [];
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_API_ACCESS_MODE ]                      =
			$this->field_api_access_mode();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_TERMINAL_CODE ]     =
			$this->field_terminal_code();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_TERMINAL_PASSWORD ] =
			$this->field_terminal_password();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_CALLBACK_SECRET ]   =
			$this->field_callback_secret();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_TEST_ENVIRONMENT ]  =
			$this->field_test_environment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_CAPTURE_PAYMENT ]                      =
			$this->field_capture_payment();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_ENABLED ]                  =
			$this->field_installment_enabled();

		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ]           = $this->field_installment_type();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MINIMUM_INSTALLMENT_AMOUNT ] =
			$this->field_minimum_installment_amount();

		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS ] =
			$this->field_maximum_accepted_installments();

		if ( 'payment_page' === get_option( self::FIELDNAME_PREFIX . self::FIELD_API_ACCESS_MODE ) ) {
			$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ]['options'] = [
				'IF' => __( 'Issuer financed', 'unlimit' ),
			];
		}

		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_PAYMENT_TITLE ] = $this->field_payment_title();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_ASK_CPF ]                          = $this->field_ask_cpf();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_DYNAMIC_DESCRIPTOR ]               =
			$this->field_dynamic_descriptor();
		$form_fields[ self::FIELDNAME_PREFIX . WC_Unlimit_Admin_Fields::FIELD_LOG_TO_FILE ]   = $this->field_log_to_file();

		return $form_fields;
	}

	/**
	 * @return array
	 */
	public function field_title( $title = null ) {
		return parent::field_title( 'Credit card - Unlimit' );
	}

	/**
	 * @return array
	 */
	public function field_capture_payment() {
		return [
			'title'       => __( 'Capture payment', 'unlimit' ),
			'type'        => 'select',
			'description' => __(
				                 "Setting is for regular payments and Merchant financed installments. If set to \"No\",",
				                 'unlimit' ) . ' ' .
			                 __( "the amount will not be captured but only blocked.", 'unlimit' ) . '<br>' .
			                 __( "By default with \"No\" option selected payments will be voided automatically in 7 days",
				                 'unlimit' ) . "<br>" .
			                 __( "from the time of creating the preauthorized transaction.", 'unlimit' ) . '<br>' .
			                 __( "If you want payments to be captured automatically in 7 days (instead of being voided),",
				                 'unlimit' ) . ' ' .
			                 __( "please contact your account manager.", 'unlimit' ),
			'default'     => 'yes',
			'options'     => [
				'no'  => __( 'No', 'unlimit' ),
				'yes' => __( 'Yes', 'unlimit' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_installment_enabled() {
		return [
			'title'       => __( 'Installment enabled', 'unlimit' ),
			'type'        => 'select',
			'description' => __( 'If set to Yes then installment payment field will be', 'unlimit' ) . ' ' .
			                 __( "presented on payment form and installment payments can be possible for processing",
				                 'unlimit' ),
			'default'     => 'no',
			'options'     => [
				'no'  => __( 'No', 'unlimit' ),
				'yes' => __( 'Yes', 'unlimit' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_installment_type() {
		$nameIF = ' < a href = "https://integration.unlimit.com/doc-guides/yjri881uncqhc-overview#issuer-financed-if" > ' .
		          __( 'Issuer financed installments', 'unlimit' ) . ' </a > ';
		$nameMF = '<a href = "https://integration.unlimit.com/doc-guides/yjri881uncqhc-overview#merchant-financed-mf_hold" > '
		          . __( 'MF HOLD installments', 'unlimit' ) . ' </a > ';

		return [
			'title'       => __( 'Installment type', 'unlimit' ),
			'type'        => 'select',
			'description' =>
				__( "Should be selected only if \"Installment enabled\" setting is switched on.", 'unlimit' ) . ' ' .
				__( "Here can be choosed installment type used in trade plugin.", 'unlimit' ) . "<br>" .
				__( 'More details about installment types you can read', 'unlimit' ) .
				' ' . $nameIF . ', ' . $nameMF,
			'default'     => 'no',
			'options'     => [
				'IF'      => __( 'Issuer financed', 'unlimit' ),
				'MF_HOLD' => __( 'Merchant financed', 'unlimit' ),
			]
		];
	}

	/**
	 * @return array
	 */
	public function field_minimum_installment_amount() {
		return [
			'title'             => __( 'Minimum installment amount',
					'unlimit' ) . ', ' . get_woocommerce_currency_symbol(),
			'type'              => 'number',
			'description'       => __( 'Minimum installment amount for order with installments.', 'unlimit' )
			                       . '<br>'
			                       . __( 'Here can be filled minimum amount of 1 installment,', 'unlimit' ) . ' ' .
			                       __( 'f.e if we have 5 installments with 20 usd amount of 1 installment,', 'unlimit' ) . ' ' .
			                       __( 'total amount of order in this case is 100 usd', 'unlimit' ),
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
	public function field_maximum_accepted_installments() {
		return [
			'title'       => __( "Allowed installments range", 'unlimit' ),
			'type'        => 'text',
			'description' =>
				__( "Allowed installments range", 'unlimit' )
				. ',' . '<br> '
				. __( "For \"Merchant financed\" installments can be filled in", 'unlimit' ) . ' ' .
				__( "range of allowed values or several allowed values not in a row.",
					'unlimit' )
				. '<br>'
				. __( "All values can be from interval 1-12, for example:", 'unlimit' ) . ' ' .
				__( "Range of values 3-7 (using \"-\" as separator).", 'unlimit' ) . ' ' .
				__( "Allowed values not in a row 2, 3, 6, 8, 12 (using \",\" as separator).", 'unlimit' )
				. '<br>'
				. __( "For \"Issuer financed\" installment type can be only allowed", 'unlimit' ) . ' ' .
				__( "values not in a row from the following: 3, 6, 9, 12, 18.", 'unlimit' )
				. '<br>'
				. __( "If empty, then the default values will be used", 'unlimit' ) . ' ' .
				__( "(2-12 for \"Merchant financed\" and 3, 6, 9, 12, 18 for \"Issuer financed\").",
					'unlimit' )
			,
			'default'     => '1'
		];
	}

	/**
	 * @return array
	 */
	public function field_payment_title( $title = null ) {
		return parent::field_payment_title( 'Credit card - Unlimit' );
	}

	/**
	 * @return array
	 */
	public function field_ask_cpf() {
		return [
			'title'   => __( 'Ask CPF', 'unlimit' ),
			'type'    => 'select',
			'default' => 'no',
			'options' => [
				'no'  => __( 'No', 'unlimit' ),
				'yes' => __( 'Yes', 'unlimit' ),
			],
		];
	}

	/**
	 * @return array
	 */
	public function field_dynamic_descriptor() {
		return [
			'title'   => __( 'Dynamic descriptor', 'unlimit' ),
			'type'    => 'text',
			'default' => '',
		];
	}

	public function field_api_access_mode() {
		$bankcard_alert_translations = $this->get_bankcard_alert_translations( [
			'MERCHANT_FINANCED' => __( 'Merchant financed', 'unlimit' ),
			'API_ACCESS_MODE'   =>
				__( 'API access mode is changed, please check Terminal code, Terminal password, Callback secret values.',
					'unlimit' ) . ' ' .
				__( 'After changing of the API mode in plugin also must be changed API access mode in Unlimit.', 'unlimit' ) . ' ' .
				__( 'Please consult about it with Unlimit support.', 'unlimit' ),
		] );

		echo "
  			<script type='text/javascript'>
  			if (typeof BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE === 'undefined') {
    			var BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE = $bankcard_alert_translations;
    		}
			</script>
		";

		return [
			'title'       => __( 'API access mode', 'unlimit' ),
			'type'        => 'select',
			'description' => __( "If \"Payment page\" mode is selected - payment page by Unlimit", 'unlimit' ) . ' ' .
			                 __( "in iFrame is used for customer data collecting.", 'unlimit' )
			                 . ' < br>'
			                 . __( "If \"Gateway\" mode is selected - embedded payment form in plugin", 'unlimit' ) . ' ' .
			                 __( "is used for customer data collecting.", 'unlimit' ),
			'default'     => 'Payment page',
			'options'     => [
				'payment_page' => __( 'Payment page', 'unlimit' ),
				'gateway'      => __( 'Gateway', 'unlimit' ),
			],
			'onkeyup'     => "formatUlCardField(this.id);",
		];
	}

	/**
	 * @param array $bankcard_translations_change_mode
	 *
	 * @return string
	 */
	public function get_bankcard_alert_translations( $bankcard_translations_change_mode ): string {
		$bankcard_alert_translations = '{';
		foreach ( $bankcard_translations_change_mode as $key => $value ) {
			$bankcard_alert_translations .= "\"$key\":\"$value\"";
			if ( array_key_last( $bankcard_translations_change_mode ) != $key ) {
				$bankcard_alert_translations .= ',';
			}
		}
		$bankcard_alert_translations .= '}';

		return $bankcard_alert_translations;
	}
}
