<?php

abstract class RY_WT_ECPay_Api extends RY_WT_Api
{
    protected const Encrypt_Method = 'aes-128-cbc';

    protected function get_3rd_return_url($order = null)
    {
        $return_url = WC()->api_request_url('ry_ecpay_gateway_return');
        if ($order) {
            $return_url = add_query_arg('id', $order->get_id(), $return_url);
            $return_url = add_query_arg('key', $order->get_order_key(), $return_url);
        }

        return $return_url;
    }

    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $this->pre_generate_trade_no($order_ID, $order_prefix);
        $trade_no = apply_filters('ry_ecpay_trade_no', $trade_no, $order_ID);
        return substr($trade_no, 0, 18);
    }

    protected function add_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $args['CheckMacValue'] = $this->generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args);
        return $args;
    }

    protected function build_args($data, $MerchantID)
    {
        $args = [
            'MerchantID' => $MerchantID,
            'RqHeader' => [
                'Timestamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
                'Revision' => '1.0.0',
            ],
            'Data' => wp_json_encode($data),
        ];
        $args['RqHeader']['Timestamp'] = $args['RqHeader']['Timestamp']->getTimestamp();

        return $args;
    }

    protected function urlencode($string)
    {
        return str_replace(
            ['%2D', '%2d', '%5F', '%5f', '%2E', '%2e', '%2A', '%2a', '%21', '%28', '%29'],
            ['-', '-', '_', '_', '.', '.', '*', '*', '!', '(', ')'],
            urlencode($string),
        );
    }

    protected function generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $skip_args[] = 'CheckMacValue';
        foreach ($skip_args as $key) {
            unset($args[$key]);
        }

        ksort($args, SORT_STRING | SORT_FLAG_CASE);

        $args_string = [];
        $args_string[] = 'HashKey=' . $HashKey;
        foreach ($args as $key => $value) {
            $args_string[] = $key . '=' . $value;
        }
        $args_string[] = 'HashIV=' . $HashIV;

        $args_string = $this->urlencode(implode('&', $args_string));
        $check_value = hash($hash_algo, strtolower($args_string));
        return strtoupper($check_value);
    }

    protected function link_server($url, $args)
    {
        wc_set_time_limit(40);

        $send_body = [];
        foreach ($args as $key => $value) {
            $send_body[] = $key . '=' . $value;
        }

        return wp_remote_post($url, [
            'timeout' => 30,
            'body' => implode('&', $send_body),
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);
    }

    protected function link_v2_server($url, $args, $HashKey, $HashIV)
    {
        wc_set_time_limit(40);

        $args['Data'] = $this->urlencode($args['Data']);
        $args['Data'] = openssl_encrypt($args['Data'], self::Encrypt_Method, $HashKey, 0, $HashIV);

        return wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($args),
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);
    }

    protected function get_check_value($ipn_info)
    {
        if (isset($ipn_info['CheckMacValue'])) {
            return $ipn_info['CheckMacValue'];
        }
        return false;
    }

    protected function get_status($ipn_info)
    {
        if (isset($ipn_info['RtnCode'])) {
            return (int) $ipn_info['RtnCode'];
        }
        return false;
    }

    protected function get_status_msg($ipn_info)
    {
        if (isset($ipn_info['RtnMsg'])) {
            return $ipn_info['RtnMsg'];
        }
        return false;
    }

    protected function get_transaction_id($ipn_info)
    {
        if (isset($ipn_info['TradeNo'])) {
            return $ipn_info['TradeNo'];
        }
        return false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['MerchantTradeNo'])) {
            $order_ID = $this->trade_no_to_order_no($ipn_info['MerchantTradeNo'], $order_prefix);
            $order_ID = apply_filters('ry_ecpay_trade_no_to_order_id', $order_ID, $ipn_info['MerchantTradeNo']);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }
        return false;
    }
}
