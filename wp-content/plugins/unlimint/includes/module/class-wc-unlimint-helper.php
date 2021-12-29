<?php

defined( 'ABSPATH' ) || exit;

class WC_Unlimint_Helper {

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

		if ( method_exists( $order, 'get_meta' ) ) {
			return $order->get_meta( $key );
		}

		return get_post_meta( $order->get_id(), $key, true );
	}

	public static function set_order_meta( $order, $key, $value ) {
		if ( ! is_object( $order ) || empty( $key ) || empty( $value ) ) {
			return;
		}

		// WooCommerce 3.0 or later.
		if ( method_exists( $order, 'update_meta_data' ) ) {
			$order->update_meta_data( $key, $value );
		} else {
			update_post_meta( $order, $key, $value );
		}
	}

	public static function mask_card_pan( $card_pan ) {
		if ( is_null( $card_pan ) || strlen( $card_pan ) <= 10 ) {
			return $card_pan;
		}

		return substr( $card_pan, 0, 6 ) . '...' . substr( $card_pan, - 4 );
	}
}
