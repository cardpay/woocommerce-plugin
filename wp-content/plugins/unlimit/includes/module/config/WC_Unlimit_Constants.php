<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimit_Constants {
	public const VERSION = '1.4.12';

	public const API_UL_BASE_URL = 'https://cardpay.com/api';
	public const API_UL_SANDBOX_URL = 'https://sandbox.cardpay.com/api';

	public const BANKCARD_GATEWAY = 'WC_Unlimit_Custom_Gateway';
	public const APAY_GATEWAY = 'WC_Unlimit_Apay_Gateway';

	public const BOLETO_GATEWAY = 'WC_Unlimit_Ticket_Gateway';
	public const PIX_GATEWAY = 'WC_Unlimit_Pix_Gateway';
	public const PAYPAL_GATEWAY = 'WC_Unlimit_Paypal_Gateway';
	public const SPEI_GATEWAY = 'WC_Unlimit_Spei_Gateway';
	public const GPAY_GATEWAY = 'WC_Unlimit_Gpay_Gateway';
	public const MBWAY_GATEWAY = 'WC_Unlimit_Mbway_Gateway';
	public const SEPA_GATEWAY = 'WC_Unlimit_Sepa_Gateway';
	public const OXXO_GATEWAY = 'WC_Unlimit_Oxxo_Gateway';
	public const MULTIBANCO_GATEWAY = 'WC_Unlimit_Multibanco_Gateway';

	public const PAYMENT_GATEWAYS = [
		self::BANKCARD_GATEWAY,
		self::APAY_GATEWAY,
		self::BOLETO_GATEWAY,
		self::GPAY_GATEWAY,
		self::MBWAY_GATEWAY,
		self::MULTIBANCO_GATEWAY,
		self::PIX_GATEWAY,
		self::PAYPAL_GATEWAY,
		self::SEPA_GATEWAY,
		self::SPEI_GATEWAY,
		self::OXXO_GATEWAY,
	];

	// order meta fields (for 'wp_postmeta' DB table)
	public const ORDER_META_PAYMENT_TYPE_FIELDNAME = '_ul_payment_type';
	public const PAYMENT_TYPE_PAYMENT = 'payment';

	public const ORDER_META_GATEWAY_FIELDNAME = '_ul_used_gateway';
	public const ORDER_META_REDIRECT_URL_FIELDNAME = '_ul_redirect_url';
	public const ORDER_META_PREAUTH_FIELDNAME = '_ul_preauth';
	public const ORDER_META_CALLBACK_STATUS_FIELDNAME = '_ul_callback_status';
	public const ORDER_META_INITIAL_API_TOTAL = '_ul_initial_api_order_total';
	public const ORDER_META_FIELD_INSTALLMENT_TYPE = '_ul_field_installment_type';
	public const ORDER_META_COUNT_INSTALLMENT = '_ul_field_count_installment_type';

	public const PAYMENT_METHOD = 'payment_method';

	// Transaction statuses
	public const TRANSACTION_STATUS_CHARGED_BACK = 'CHARGED_BACK';
	public const TRANSACTION_STATUS_COMPLETED = 'COMPLETED';
	public const TRANSACTION_STATUS_VOIDED = 'VOIDED';
	public const TRANSACTION_STATUS_REFUNDED = 'REFUNDED';

	// Other constants
	public const SECURITY_CODE_MASKED = '...';

	const PAYMENT_DATA = 'payment_data';

	const LANGUAGES = [
		'en',
		'zh',
		'hy',
		'pl',
		'az',
		'bg',
		'cs',
		'es',
		'ka',
		'el',
		'hu',
		'id',
		'ja',
		'ms',
		'pt',
		'ro',
		'ru',
		'sr',
		'ar',
		'th',
		'tr',
		'uk',
		'vi',
		'de',
		'fr',
		'it',
		'sv',
		'nl'
	];
}