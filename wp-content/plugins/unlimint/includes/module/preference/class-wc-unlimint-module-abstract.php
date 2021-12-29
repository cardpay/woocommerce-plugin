<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

abstract class WC_Unlimint_Module_Abstract extends WC_Payment_Gateway {

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var WC_Unlimint_Payment_Abstract
	 */
	protected $payment;

	/**
	 * @var WC_Unlimint_Logger
	 */
	protected $logger;

	/**
	 * @var array
	 */
	protected $post_fields;

	/**
	 * @var int
	 */
	protected $order_total;

	/**
	 * @var array
	 */
	protected $api_request;

	/**
	 * @var mixed
	 */
	protected $selected_shipping;

	/**
	 * @var mixed
	 */
	protected $ship_cost;

	/**
	 * @var false|mixed|void
	 */
	protected $site_id;

	/**
	 * @var array
	 */
	protected $site_data;

	/**
	 * @var false|mixed|void
	 */
	protected $test_user_v1;

	/**
	 * @var bool
	 */
	protected $sandbox;

	/**
	 * @var false|string
	 */
	protected $notification_class;

	/**
	 * @var array
	 */
	protected $ex_payments;

	/**
	 * @var string
	 */
	protected $installments;

	/**
	 * @param WC_Unlimint_Payment_Abstract $payment
	 * @param WC_Order $order
	 * @param null $post_fields
	 *
	 * @throws Exception Preference Init abstract exception.
	 */
	public function __construct( $payment, $order, $post_fields = null ) {
		$this->payment            = $payment;
		$this->logger             = $payment->logger;
		$this->order              = $order;
		$this->ex_payments        = $this->payment->ex_payments;
		$this->installments       = $this->payment->installments;
		$this->notification_class = get_class( $this->payment );
		$this->sandbox            = $this->payment->is_test_user();
		$this->test_user_v1       = get_option( '_test_user_v1', '' );
		$this->site_id            = get_option( '_site_id_v1', '' );
		$this->site_data          = null;
		$this->order              = $order;
		$this->post_fields        = $post_fields;

		$this->order_total       = 0;
		$this->selected_shipping = $order->get_shipping_method();
	}

	/**
	 * @return array
	 */
	public function get_common_api_request() {
		$notification_url = $this->get_notification_url();
		$order_id         = $this->order->get_id();
		$customer         = WC()->customer;

		$common_api_request = [
			'request'        => [
				'id'   => uniqid( '', true ),
				'time' => date( "Y-m-d\TH:i:s\Z" ),
			],
			'merchant_order' => [
				'id'               => $order_id,
				'description'      => "Order #$order_id",
				'shipping_address' => [
					'country'     => $customer->get_shipping_country(),
					'state'       => $customer->get_shipping_state(),
					'zip'         => $customer->get_shipping_postcode(),
					'city'        => $customer->get_shipping_city(),
					'phone'       => $customer->get_shipping_phone(),
					'addr_line_1' => $customer->get_shipping_address_1()
				]
			],
			'customer'       => [
				'id'    => $this->order->get_user_id(),
				'email' => $this->order->get_billing_email(),
				'phone' => $this->order->get_billing_phone(),
			],
			'return_urls'    => [
				'decline_url'   => $this->build_return_url( $notification_url, 'decline', $order_id ),
				'inprocess_url' => $this->build_return_url( $notification_url, 'inprocess', $order_id ),
				'success_url'   => $this->build_return_url( $notification_url, 'success', $order_id )
			]
		];

		$items = $this->get_items();
		if ( ! empty( $items ) ) {
			$common_api_request['merchant_order']['items'] = $items;
		}

		return $common_api_request;
	}

	private function build_return_url( $notification_url, $action, $order_id ) {
		$delimiter = '?';
		if ( strpos( $notification_url, '?' ) !== false ) {
			$delimiter = '&';
		}

		return $notification_url . $delimiter . 'action=' . $action . '&order_id=' . $order_id;
	}

	/**
	 * @return string|null
	 */
	public function get_notification_url() {
		$notification_url = $this->payment->custom_domain;

		// Check if we have a custom URL.
		if ( empty( $notification_url ) || filter_var( $notification_url, FILTER_VALIDATE_URL ) === false ) {
			return WC()->api_request_url( $this->notification_class );
		}

		return WC_Unlimint_Module::fix_url_ampersand(
			esc_url(
				$notification_url . '/wc-api/' . $this->notification_class . '/'
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_api_request() {
		return $this->api_request;
	}

	/**
	 * @return array
	 */
	private function get_items() {
		$items = [];

		foreach ( $this->order->get_items() as $item ) {
			$items[] = [
				'name'        => $item->get_name(),
				'description' => 'Item #' . $item->get_id(),
				'count'       => $item->get_quantity(),
				'price'       => $item->get_total()
			];
		}

		return $items;
	}
}