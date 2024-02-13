<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// if uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Only remove ALL product and page data if WC_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
$order_status_fields = [
	'order_status_new',
	'order_status_in_progress',
	'order_status_declined',
	'order_status_authorized',
	'order_status_completed',
	'order_status_canceled',
	'order_status_voided',
	'order_status_refunded',
	'order_status_charged_back',
	'order_status_chargeback_resolved',
	'order_status_terminated',
];

foreach ( $order_status_fields as $field ) {
	delete_option( "woocommerce_unlimit_{$field}" );
}

$delete_options = [
	'bankcard'   => [
		'payment_page',
		'capture_payment',
		'installment_enabled',
		'minimum_installment_amount',
		'maximum_accepted_installments',
		'ask_cpf',
		'dynamic_descriptor',
	],
	'apay'       => [
		'merchant_certificate',
		'apple_merchant_id',
		'merchant_key',
	],
	'boleto'     => [],
	'gpay'       => [
		'google_merchant_id',
	],
	'mbway'      => [
		'payment_page',
	],
	'multibanco' => [
		'payment_page',
	],
	'paypal'     => [
		'payment_page',
	],
	'pix'        => [],
	'sepa'       => [
		'payment_page',
	],
	'spei'       => [
		'payment_page',
	],
	// Добавьте сюда другие опции, если необходимо
];

$common_fields = [
	'terminal_code',
	'terminal_password',
	'callback_secret',
	'test_environment',
	'payment_title',
	'log_to_file',
];

foreach ( $delete_options as $option => $fields ) {
	foreach ( $fields as $field ) {
		delete_option( "woocommerce_unlimit_{$option}_{$field}" );
	}

	foreach ( $common_fields as $common_field ) {
		delete_option( "woocommerce_unlimit_{$option}_{$common_field}" );
	}
}
$delete_options = [
	'custom',
	'apay',
	'ticket',
	'gpay',
	'mbway',
	'multibanco',
	'paypal',
	'pix',
	'sepa',
	'spei',
];

foreach ( $delete_options as $option ) {
	delete_option( "woocommerce_woo-unlimit-{$option}_settings" );
}
