<?php

abstract class RY_WT_SmilePay_Api extends RY_WT_Api
{
    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $this->pre_generate_trade_no($order_ID, $order_prefix);
        $trade_no = apply_filters('ry_smilepay_trade_no', $trade_no, $order_ID);

        return substr($trade_no, 0, 18);
    }

    protected function link_server($url, $args)
    {
        wc_set_time_limit(40);

        return wp_remote_post($url . '?' . http_build_query($args, '', '&'), [
            'timeout' => 30,
        ]);
    }

    protected function clean_post_data($change_convert = false)
    {
        if ($change_convert) {
            if (function_exists('mb_convert_encoding')) {
                foreach ($_POST as $key => $value) {
                    if (!is_array($value)) {
                        $_POST[$key] = mb_convert_encoding($value, 'UTF-8', 'BIG-5');
                    } else {
                        unset($_POST[$key]);
                    }
                }
            }
        }

        $ipn_info = [];
        foreach ($_POST as $key => $value) {
            if (!is_array($value)) {
                $ipn_info[$key] = wp_unslash($value);
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

    protected function get_check_value($ipn_info)
    {
        if (isset($ipn_info['Mid_smilepay'])) {
            return $ipn_info['Mid_smilepay'];
        }

        return false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['Data_id'])) {
            $order_ID = $this->trade_no_to_order_no($ipn_info['Data_id'], $order_prefix);
            $order_ID = apply_filters('ry_smilepay_trade_no_to_order_id', $order_ID, $ipn_info['Data_id']);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }

        return false;
    }

    protected function die_success()
    {
        exit('<Roturlstatus>RY_SmilePay</Roturlstatus>');
    }
}
