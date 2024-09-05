<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

class WC_Unlimit_Helper {

	/**
	 * @param WC_Order $order
	 * @param string $key
	 *
	 * @return mixed
	 */
	public static function get_order_meta( $order, $key ) {
		if ( ! is_object( $order ) || empty( $key ) ) {
			return null;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order = wc_get_order( $order->get_id() );

			return $order->get_meta( $key, true );
		} else {
			return get_post_meta( $order->get_id(), $key, true );
		}
	}

	public static function set_order_meta( $order, $key, $value ) {
		if ( ! is_object( $order ) || empty( $key ) || empty( $value ) ) {
			return;
		}

		// WooCommerce 3.0 or later.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order->update_meta_data( $key, $value );
			$order->save();
		} else {
			update_post_meta( $order->get_id(), $key, $value );
		}
	}

	public static function mask_card_pan( $card_pan ) {
		if ( is_null( $card_pan ) || strlen( $card_pan ) <= 10 ) {
			return $card_pan;
		}

		return substr( $card_pan, 0, 6 ) . '...' . substr( $card_pan, - 4 );
	}
}
