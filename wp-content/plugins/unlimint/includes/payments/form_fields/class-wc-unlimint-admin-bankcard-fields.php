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
	public const FIELD_MINIMUM_INSTALLMENT_AMOUNT = 'minimum_installment_amount';
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

		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_INSTALLMENT_TYPE ]           = $this->field_installment_type();
		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MINIMUM_INSTALLMENT_AMOUNT ] = $this->field_minimum_installment_amount();

		$form_fields[ self::FIELDNAME_PREFIX . self::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS ] = $this->field_maximum_accepted_installments();

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
			'description' => __( "Setting is for regular payments and Merchant Financed installments. If set to \"No\", the amount will not be captured but only blocked. By default with \"No\" option selected payments will be voided automatically in 7 days from the time of creating the preauthorized transaction.<br>If you want payments to be captured automatically in 7 days (instead of being voided), please contact your account manager.", 'unlimint' ),
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
			'description' => __( 'If set to Yes then installment payment field will be presented on payment form and installment payments can be possible for processing', 'unlimint' ),
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
			'description' => __( "Should be selected only if \"Installment enabled\" setting is switched on. Here can be choosed Installment type used in trade plugin.", 'unlimint' ) . "<br>" . __( 'More details about installment types you can read', 'unlimint' ) . ' ' . '<a href="https://integration.unlimint.com/#Issuer-financed-(IF)">' . $nameIF . ', ' . $nameMF,
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
	public function field_minimum_installment_amount() {
		return [
			'title'             => __( 'Minimum installment amount', 'unlimint' ) . ', ' . get_woocommerce_currency_symbol(),
			'type'              => 'number',
			'description'       => __( 'Minimum installment amount for order with installments.', 'unlimint' )
			                       . '<br>'
			                       . __( 'Here can be filled minimum amount of 1 installment, f.e if we have 5 installments with 20 usd amount of 1 installment, total amount of order in this case is 100 usd', 'unlimint' ),
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
			'title'       => __( 'Allowed installments range', 'unlimint' ),
			'type'        => 'text',
			'description' =>
				__( 'Allowed installments range', 'unlimint' )
				. ',<br>'
				. __( 'For "Merchant Financed" installments can be filled in range of allowed values or several allowed values not in a row.', 'unlimint' )
				. '<br>'
				. __( 'All values can be from interval 1-12, for example: Range of values 3-7 (using "-" as separator). Allowed values not in a row 2, 3, 6, 8, 12 (using "," as separator).', 'unlimint' )
				. '<br>'
				. __( 'For "Issuer financed" Installment type can be only allowed values not in a row from the following: 3, 6, 9, 12, 18.', 'unlimint' )
				. '<br>'
				. __( 'If empty, then the default values will be used (2-12 for "Merchant Financed" and 3, 6, 9, 12, 18 for "Issuer Financed").', 'unlimint' )
			,
			'default'     => '1'
		];
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
		$bankcard_translations_change_mode = [
            'MERCHANT_FINANCED'=>__('Merchant financed', 'unlimint'),
			'API_ACCESS_MODE' => __( 'API access mode is changed, please check Terminal code, terminal password, callback secret values. After changing of the API mode in plugin also must be changed API access mode in Unlimint. Please consult about it with Unlimint support.', 'unlimint' ),
		];
		$bankcard_alert_translations       = '{';
		foreach ( $bankcard_translations_change_mode as $key => $value ) {
			$bankcard_alert_translations .= "\"$key\":\"$value\"";
			if ( array_key_last( $bankcard_translations_change_mode ) != $key ) {
				$bankcard_alert_translations .= ',';
			}
		}
		$bankcard_alert_translations .= '}';

		echo "
			<script type='text/javascript'>
			if (typeof BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE === 'undefined') {
                var BANKCARD_ALERT_TRANSLATIONS_CHANGE_MODE = $bankcard_alert_translations;
            }
			</script>
		";

		return [
			'title'       => __( 'API access mode', 'unlimint' ),
			'type'        => 'select',
			'description' => __(
				                 "If \"Payment page\" mode is selected - payment page by Unlimint in iFrame is used for customer data collecting.", 'unlimint' )
			                 . '<br>'
			                 . __( "If \"Gateway\" mode is selected - embedded payment form in plugin is used for customer data collecting.", 'unlimint' ),
			'default'     => 'Payment page',
			'options'     => [
				'payment_page' => __( 'Payment page', 'unlimint' ),
				'gateway'      => __( 'Gateway', 'unlimint' ),
			],
			'onkeyup'     => "formatUlCardField(this.id);",
		];
	}
}