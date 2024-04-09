<?php

defined('ABSPATH') || exit;

require_once __DIR__ . '/../module/config/WC_Unlimit_Constants.php';
require_once __DIR__ . '/../module/WC_Unlimit_Helper.php';
require_once __DIR__ . '/WC_Unlimit_Order_Status_Updater.php';

class WC_Unlimit_Refund
{
    const ERROR_BOLETO = 'Refund is not available for Boleto';
    const ERROR_PIX = 'Refund is not available for Pix';
    const ERROR_SPEI = 'Refund is not available for SPEI';
    const ERROR_SEPA = 'Refund is not available for SEPA Instant';
    const ERROR_OXXO = 'Refund is not available for OXXO';
    const ERROR_STATUS = "Refund can be made in 'Processing' or 'Completed' order status only";

    const ALLOWED_ORDER_STATUSES = ['processing', 'completed'];

    /**
     * @var WC_Unlimit_Logger
     */
    public $logger;

    /**
     * @var WC_Unlimit_Sdk
     */
    public $unlimit_sdk;

    public function __construct($gateway_id)
    {
        $this->logger = new WC_Unlimit_Logger();

        $this->unlimit_sdk = new WC_Unlimit_Sdk($gateway_id);
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $this->logger->log_callback_request(__FUNCTION__, 'Refund processing has started');

        try {
            $this->validate_order_for_refund($amount, $order_id);
            $this->logger->log_callback_request(__FUNCTION__, 'Refund validation passed');
        } catch (WC_Unlimit_Exception $e) {
            $error_message = $e->getMessage();
            $this->logger->error(__FUNCTION__, $error_message);

            return new WP_Error(
                'wc_ul_refund_failed',
                __("Refund for order", "unlimit") .
                ' ' .
                "#$order_id" .
                ' ' .
                __("has failed with validation error:", "unlimit") .
                ' ' .
                __("$error_message", "unlimit")
            );
        }

        $request      = $this->get_refund_request($order_id, $amount, $reason);
        $request_json = json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->logger->log_callback_request(__FUNCTION__, "Sending refund API request: " . $request_json);

        $refund_info = $this->unlimit_sdk->post('/refunds', wp_json_encode($request));
		$completeArray = ['AUTHORIZED', 'COMPLETED', 'REFUNDED'];
        if (
			isset($refund_info['status']) &&
			(int)$refund_info['status'] === 201 &&
			in_array($refund_info['response']['refund_data']['status'], $completeArray)
        ) {
            $this->handle_refund_status($order_id, $refund_info['response']);

            $this->logger->log_callback_request(
                __FUNCTION__,
                "Refund processing successful for order #$order_id: " .
                wp_json_encode(
                    $refund_info,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );

            return true;
        }

        $error_message = '';
        if (isset($refund_info['response']['message'])) {
            $error_message = $refund_info['response']['message'];
            $this->logger->error(
                __FUNCTION__,
                "Refund for order #$order_id has failed with error: " . $error_message
            );
        }

        return new WP_Error(
            'wc_ul_api_refund_failed',
            "Refund for order #$order_id has failed with API error: $error_message"
        );
    }

    private function validate_order_for_refund($amount, $order_id)
    {
        if (is_null($amount) || (float)$amount <= 0) {
            throw new WC_Unlimit_Exception(__("Invalid refund amount for order", "unlimit") . ' ' . "#$order_id");
        }

        $order   = wc_get_order($order_id);
        $gateway = WC_Unlimit_Helper::get_order_meta($order, WC_Unlimit_Constants::ORDER_META_GATEWAY_FIELDNAME);

        if (WC_Unlimit_Constants::BOLETO_GATEWAY === $gateway) {
            throw new WC_Unlimit_Exception(__(self::ERROR_BOLETO));
        }

        if (WC_Unlimit_Constants::PIX_GATEWAY === $gateway) {
            throw new WC_Unlimit_Exception(__(self::ERROR_PIX));
        }

        if (WC_Unlimit_Constants::SPEI_GATEWAY === $gateway) {
            throw new WC_Unlimit_Exception(__(self::ERROR_SPEI));
        }

        if (WC_Unlimit_Constants::OXXO_GATEWAY === $gateway) {
            throw new WC_Unlimit_Exception(__(self::ERROR_OXXO));
        }

        if (WC_Unlimit_Constants::SEPA_GATEWAY === $gateway) {
            throw new WC_Unlimit_Exception(__(self::ERROR_SEPA));
        }

        if ( ! method_exists($order, 'get_status')) {
            return;
        }

        if ( ! in_array($order->get_status(), self::ALLOWED_ORDER_STATUSES, true)) {
            throw new WC_Unlimit_Exception(__(self::ERROR_STATUS));
        }
    }

    private function get_refund_request($order_id, $amount, $reason)
    {
        $order = wc_get_order($order_id); // you can get $order->get_transaction_id()

        return [
            'request'        => [
                'id'   => uniqid('', true),
                'time' => date("Y-m-d\TH:i:s\Z")
            ],
            'merchant_order' => [
                'description' => ! empty($reason) ? $reason : "Refund for order #$order_id",
            ],
            'payment_data'   => [
                'id' => $order->get_transaction_id()
            ],
            'refund_data'    => [
                'amount'   => $amount,
                'currency' => get_woocommerce_currency()
            ],
        ];
    }

    /**
     * @param $order_id
     * @param $response
     */
    private function handle_refund_status($order_id, $response): void
    {
        $order            = wc_get_order($order_id);
        $remaining_amount = isset($response['payment_data']['remaining_amount']) ?
            $response['payment_data']['remaining_amount'] : null;
        if (isset($remaining_amount) && (0 === (int)$remaining_amount)) {
            $order_status_updater = new WC_Unlimit_Order_Status_Updater();
            $order_status_updater->update_order_status(
                $order,
                WC_Unlimit_Admin_Order_Status_Fields::REFUNDED_WC
            );
        }
    }
}