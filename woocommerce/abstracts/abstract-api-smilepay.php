<?php

defined('ABSPATH') or exit;

abstract class RY_WT_SmilePay_Api extends RY_WT_Api
{
    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $this->pre_generate_trade_no($order_ID, $order_prefix);
        $trade_no = apply_filters('ry_smilepay_trade_no', $trade_no, $order_ID);

        return substr($trade_no, 0, 18);
    }

    protected function link_server(string $url, array $args, int $timeout = 20)
    {
        wc_set_time_limit(40);

        return wp_remote_post($url . '?' . http_build_query($args, '', '&'), [
            'timeout' => $timeout,
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);
    }

    protected function convert_encoding($ipn_info)
    {
        if (function_exists('mb_convert_encoding')) {
            foreach ($ipn_info as $key => $value) {
                if (!is_array($value)) {
                    $ipn_info[$key] = mb_convert_encoding($value, 'UTF-8', 'BIG-5');
                } else {
                    $ipn_info[$key] = $this->convert_encoding($value);
                }
            }
        }

        return $ipn_info;
    }

    protected function get_transaction_id($ipn_info)
    {
        if (isset($ipn_info->SmilePayNO)) {
            return (string) $ipn_info->SmilePayNO;
        }
        if (isset($ipn_info['Smseid'])) {
            return $ipn_info['Smseid'];
        }

        return false;
    }

    protected function get_status($ipn_info)
    {
        if (isset($ipn_info['Shipstatus'])) {
            return trim($ipn_info['Shipstatus']);
        }
        if (isset($ipn_info['Status'])) {
            return trim($ipn_info['Status']);
        }

        return false;
    }

    protected function get_hash_value($ipn_info)
    {
        return $ipn_info['Mid_smilepay'] ?? false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['Data_id'])) {
            $order_ID = $this->trade_no_to_order_no($ipn_info['Data_id'], $order_prefix);
            $order_ID = (int) apply_filters('ry_smilepay_trade_no_to_order_id', $order_ID, $ipn_info['Data_id']);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }

        return false;
    }

    protected function set_transaction_info($order, $result, $payment_type)
    {
        $transaction_ID = (string) $order->get_transaction_id();
        if ($transaction_ID == '' || !$order->is_paid() || $transaction_ID != $this->get_transaction_id($result)) {
            $order->set_transaction_id($this->get_transaction_id($result));
            $order->update_meta_data('_smilepay_payment_type', $payment_type);
            $order->save();
            $order = wc_get_order($order->get_id());
        }
        return $order;
    }

    protected function die_success()
    {
        exit('<Roturlstatus>RY_SmilePay</Roturlstatus>');
    }
}
