<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Constants {
	public const VERSION = '1.0.1';

	public const API_UL_BASE_URL = 'https://cardpay.com/api';
	public const API_UL_SANDBOX_URL = 'https://sandbox.cardpay.com/api';

	public const BANKCARD_GATEWAY = 'WC_Unlimint_Custom_Gateway';
	public const BOLETO_GATEWAY = 'WC_Unlimint_Ticket_Gateway';
	public const PIX_GATEWAY = 'WC_Unlimint_Pix_Gateway';

	public const PAYMENT_GATEWAYS = [ self::BANKCARD_GATEWAY, self::BOLETO_GATEWAY, self::PIX_GATEWAY ];

	// order meta fields (for 'wp_postmeta' DB table)
	public const ORDER_META_PAYMENT_TYPE_FIELDNAME = '_ul_payment_type';
	public const PAYMENT_TYPE_PAYMENT = 'payment';
	public const PAYMENT_TYPE_RECURRING = 'recurring';

	public const ORDER_META_GATEWAY_FIELDNAME = '_ul_used_gateway';
	public const ORDER_META_REDIRECT_URL_FIELDNAME = '_ul_redirect_url';
	public const ORDER_META_PREAUTH_FIELDNAME = '_ul_preauth';
	public const ORDER_META_CALLBACK_STATUS_FIELDNAME = '_ul_callback_status';
	public const ORDER_META_INITIAL_API_TOTAL = '_ul_initial_api_order_total';   // initial order total amount, before the first API call

	// Transaction statuses
	public const TRANSACTION_STATUS_CHARGED_BACK = 'CHARGED_BACK';
	public const TRANSACTION_STATUS_COMPLETED = 'COMPLETED';
	public const TRANSACTION_STATUS_VOIDED = 'VOIDED';
	public const TRANSACTION_STATUS_REFUNDED = 'REFUNDED';

	// Other constants
	public const SECURITY_CODE_MASKED = '...';
}