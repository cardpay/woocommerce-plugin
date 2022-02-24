<?php
/**
 * Plugin Name: Woocommerce Unlimint Payments
 * Plugin URI: https://github.com/cardpay/woocommerce-plugin
 * Description: Unlimint Woocommerce engine plugin allows merchants to make payments, installment payments and refunds using the Woocommerce platform.
 * Version: 1.0.2
 * Author: Unlimint
 * Author URI: https://www.unlimint.com
 * Text Domain: woocommerce-unlimint
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 5.1.0
 *
 * @package Unlimint
 * @category Core
 * @author Unlimint <support@unlimint.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WC_UNLIMINT_BASENAME' ) ) {
	define( 'WC_UNLIMINT_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'SCRIPT_DEBUG' ) ) {
	define( 'SCRIPT_DEBUG', true );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! class_exists( 'WC_Unlimint_Init' ) ) {
	include_once __DIR__ . '/includes/module/class-wc-unlimint-init.php';

	register_activation_hook( __FILE__, [ 'WC_Unlimint_Init', 'unlimint_plugin_activation' ] );
	add_action( 'plugins_loaded', [ 'WC_Unlimint_Init', 'unlimint_init' ] );
}
