<?php

defined('ABSPATH') || exit;

require_once __DIR__ . '/class-wc-unlimint-module.php';
require_once __DIR__ . '/log/class-wc-unlimint-logger.php';

class WC_Unlimint_Installments
{
    /**
     * @var Unlimint_Sdk
     */
    private $sdk;

    /**
     * @var WC_Unlimint_Logger|null
     */
    private $logger;

    public function __construct()
    {
        $this->sdk = WC_Unlimint_Module::get_unlimint_sdk(WC_Unlimint_Custom_Gateway::GATEWAY_ID);
        $this->logger = new WC_Unlimint_Logger();
    }

    public function get_installment_options()
    {
        // avoid several API calls
        global $wp;
        $order_id_to_pay = $wp->query_vars['order-pay'];              // 'Pay for order' WC page
        if (1 !== (int)doing_filter('wc_ajax_update_order_review') && empty($order_id_to_pay)) {
            return [];
        }

        if (!empty($order_id_to_pay)) {
            $order = wc_get_order($order_id_to_pay);
            $total_amount = $order->get_total();
        } else {
            $cart = WC()->cart;
            if (is_null($cart)) {
                return [];
            }

            $total_amount = $cart->get_total('raw');
        }

        return [
            'currency' => get_woocommerce_currency_symbol(),
            'options' => $this->build_installment_options($total_amount)
        ];
    }

    private function build_installment_options($total_amount)
    {
        if (get_option(WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_ENABLED) === 'no') {
            return [];
        }

        $options = [];

        $getMinimumInstallmentAmount = (float)get_option(WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_MINIMUM_INSTALLMENT_AMOUNT);

        $getInstallmentsRange = $this->get_installments_range(
            get_option(WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_MAXIMUM_ACCEPTED_INSTALLMENTS)
        );

        foreach ($getInstallmentsRange as $installments) {
            $amount = $total_amount / $installments;
            if (
                ($amount < $getMinimumInstallmentAmount) &&
                ($installments > 1) &&
                ($getMinimumInstallmentAmount > 0)) {
                break;
            }
            $options[] = [
                'installments' => $installments,
                'amount' => $this->format_amount($total_amount / $installments)
            ];
        }

        return $options;
    }

    private function get_allowed_installment_range()
    {
        if (get_option(WC_Unlimint_Admin_BankCard_Fields::FIELDNAME_PREFIX . WC_Unlimint_Admin_BankCard_Fields::FIELD_INSTALLMENT_TYPE) === 'MF_HOLD') {
            return [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        } else {
            return [3, 6, 9, 12, 18];
        }
    }

    private function append_installment_option(&$result, $value, $range)
    {
        if (in_array($value, $range)) {
            $result[] = $value;
        }
    }

    private function nolmalize_installment_array($array)
    {
        $array[] = 1;
        $result = array_unique($array);
        sort($result);
        return (empty($result)) ? [1] :  $result;
    }

    private function get_installments_range($settings)
    {
        $result = [];

        $range = $this->get_allowed_installment_range();

        foreach (explode(',', trim($settings)) as $value) {
            if (strpos($value, '-') !== false) {
                $value = explode('-', $value);
                if (count($value) !== 2) {
                    continue;
                }
                for ($i = (int)$value[0]; $i <= ((int)$value[1]); $i++) {
                    $this->append_installment_option($result, $i, $range);
                }
            } else {
                $this->append_installment_option($result, (int)$value, $range);
            }
        }

        return $this->nolmalize_installment_array($result);
    }

    private function format_amount($amount)
    {
        if (empty($amount)) {
            return $amount;
        }

        return number_format($amount, 2);
    }
}