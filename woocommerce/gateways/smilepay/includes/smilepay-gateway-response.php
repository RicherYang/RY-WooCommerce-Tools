<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_SmilePay_Gateway_Response extends RY_SmilePay_Gateway_Api
{
    public static function init()
    {
        add_action('woocommerce_api_ry_smilepay_callback', [__CLASS__, 'check_callback']);

        add_action('valid_smilepay_gateway_request', [__CLASS__, 'doing_callback']);
    }

    public static function check_callback()
    {
        if (!empty($_POST)) {
            if (function_exists('mb_convert_encoding')) {
                foreach ($_POST as $key => $value) {
                    $_POST[$key] = mb_convert_encoding($value, 'UTF-8', 'BIG-5');
                }
            }
            $ipn_info = wp_unslash($_POST);
            if (self::ipn_request_is_valid($ipn_info)) {
                do_action('valid_smilepay_gateway_request', $ipn_info);
            } else {
                self::die_error();
            }
        }
    }

    protected static function ipn_request_is_valid($ipn_info)
    {
        $check_value = self::get_check_value($ipn_info);
        if ($check_value) {
            RY_SmilePay_Gateway::log('IPN request: ' . var_export($ipn_info, true));
            list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

            $order_id = self::get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
            if ($order = wc_get_order($order_id)) {
                $ipn_info_check_value = [];
                $ipn_info_check_value[0] = str_pad(substr($Rot_check, -4), 4, '0', STR_PAD_LEFT);
                $ipn_info_check_value[1] = (int) ceil($order->get_total());
                $ipn_info_check_value[1] = str_pad($ipn_info_check_value[1], 8, '0', STR_PAD_LEFT);
                $ipn_info_check_value[2] = isset($ipn_info['Smseid']) ? $ipn_info['Smseid'] : '';
                $ipn_info_check_value[2] = str_pad(substr($ipn_info_check_value[2], -4), 4, '9', STR_PAD_LEFT);
                $ipn_info_check_value[2] = preg_replace('/[^\d]/s', '9', $ipn_info_check_value[2]);

                $ipn_info_check_value = implode('', $ipn_info_check_value);
                $strlen = strlen($ipn_info_check_value);
                $odd = $even = 0;
                for ($i = 0; $i < $strlen; ++$i) {
                    if ($i%2 == 0) {
                        $even = $even + $ipn_info_check_value[$i];
                    }
                    if ($i%2 == 1) {
                        $odd = $odd + $ipn_info_check_value[$i];
                    }
                }
                $ipn_info_check_value = $even * 9 + $odd * 3;

                if ($check_value == $ipn_info_check_value) {
                    return true;
                }
            }
        }
        RY_SmilePay_Gateway::log('IPN request check failed. Response:' . $check_value . ' Self:' . $ipn_info_check_value, 'error');
        return false;
    }

    public static function doing_callback($ipn_info)
    {
        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            RY_SmilePay_Gateway::log('Found order #' . $order->get_id());

            $payment_type = '';
            switch ($ipn_info['Classif']) {
                case 'A':
                    $payment_type = 1;
                    break;
                case 'B':
                    $payment_type = 21;
                    break;
                case 'C':
                    $payment_type = 3;
                    break;
                case 'E':
                    $payment_type = 4;
                    break;
                case 'F':
                    $payment_type = 6;
                    break;
            }

            if (!$order->is_paid()) {
                if ($ipn_info['Amount'] == ceil($order->get_total())) {
                    $order = self::set_transaction_info($order, $ipn_info, $payment_type);
                    $order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
                    $order->payment_complete();
                }
            }

            switch ($payment_type) {
                case 1:
                    if ($ipn_info['Response_id'] == 0) {
                        if ($order->is_paid()) {
                            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
                            $order->save();
                        } else {
                            $order->update_status('failed', sprintf(
                                /* translators: Error status message */
                                __('Payment failed (%s)', 'ry-woocommerce-tools'),
                                $ipn_info['Errdesc']
                            ));
                        }
                    }
                    break;
            }

            do_action('ry_smilepay_gateway_response', $ipn_info, $order);

            self::die_success();
        } else {
            RY_SmilePay_Gateway::log('Order not found', 'error');
            self::die_error();
        }
    }
}
