<?php
/**
 * Plugin Name: WooCommerce Unlimit Payments
 * Plugin URI: https://github.com/cardpay/woocommerce-plugin
 * Description: Unlimit WooCommerce plugin allows merchants to make payments, installment payments and refunds.
 * Version: 1.5.55
 * Author: Unlimit
 * Author URI: https://www.unlimit.com
 * Text Domain: woocommerce-unlimit
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.0.0
 * WC tested up to: 5.1.0
 *
 * @package Unlimit
 * @category Core
 * @author Unlimit <support@unlimit.com>
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WC_UNLIMIT_BASENAME' ) ) {
	define( 'WC_UNLIMIT_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'SCRIPT_DEBUG' ) ) {
	define( 'SCRIPT_DEBUG', true );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! class_exists( 'WC_Unlimit_Init' ) ) {
	include_once __DIR__ . '/wp-content/plugins/unlimit/includes/module/WC_Unlimit_Init.php';

	register_activation_hook( __FILE__, [ 'WC_Unlimit_Init', 'unlimit_plugin_activation' ] );
	add_action( 'plugins_loaded', [ 'WC_Unlimit_Init', 'unlimit_init' ] );
}

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
