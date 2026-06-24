<?php

defined('ABSPATH') or exit;

abstract class RY_WT_PAYUNi_Api extends RY_WT_Api
{
    protected const ENCRYPT_METHOD = 'aes-256-gcm';

    protected function get_3rd_return_url($order = null)
    {
        $return_url = WC()->api_request_url('ry_payuni_gateway_return');
        if ($order) {
            $return_url = add_query_arg('id', $order->get_id(), $return_url);
            $return_url = add_query_arg('key', $order->get_order_key(), $return_url);
        }

        return $return_url;
    }

    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $this->pre_generate_trade_no($order_ID, $order_prefix);
        $trade_no = apply_filters('ry_payuni_trade_no', $trade_no, $order_ID);
        return substr($trade_no, 0, 18);
    }

    protected function generate_hash_value(string $string, string $HashKey, string $HashIV)
    {
        return strtoupper(hash('sha256', $HashKey . $string . $HashIV));
    }

    protected function build_args($data, $version)
    {
        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $api_info['MerID'],
            'EncryptInfo' => $this->data_encrypt($data, $api_info['HashKey'], $api_info['HashIV']),
            'Version' => $version,
        ];
        $args['HashInfo'] = $this->generate_hash_value($args['EncryptInfo'], $api_info['HashKey'], $api_info['HashIV']);

        return $args;
    }

    protected function data_encrypt($args, $HashKey, $HashIV)
    {
        $tag = '';
        $encrypted = @openssl_encrypt(http_build_query($args), self::ENCRYPT_METHOD, $HashKey, 0, $HashIV, $tag);
        return trim(bin2hex($encrypted . ':::' . base64_encode($tag)));
    }

    protected function data_decrypt($string, $HashKey, $HashIV)
    {
        $string = hex2bin($string);
        if (str_contains($string, ':::')) {
            list($encryptData, $tag) = explode(':::', $string, 2);
            return openssl_decrypt($encryptData, self::ENCRYPT_METHOD, $HashKey, 0, $HashIV, base64_decode($tag));
        }
        return false;
    }

    protected function link_server(string $url, array $args, int $timeout = 30)
    {
        wc_set_time_limit(40);

        return wp_remote_post($url, [
            'timeout' => $timeout,
            'body' => $args,
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);
    }

    protected function get_hash_value($ipn_info)
    {
        return $ipn_info['HashInfo'] ?? false;
    }

    protected function get_info_value($ipn_info)
    {
        return $ipn_info['EncryptInfo'] ?? false;
    }

    protected function get_status($ipn_info)
    {
        return $ipn_info['Status'] ?? false;
    }

    protected function get_status_msg($ipn_info)
    {
        return $ipn_info['Message'] ?? false;
    }

    protected function get_transaction_id($ipn_info)
    {
        return $ipn_info['TradeNo'] ?? false;
    }

    protected function get_payment_type($ipn_info)
    {
        return $ipn_info['PaymentType'] ?? false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['MerTradeNo'])) {
            $order_ID = $this->trade_no_to_order_no($ipn_info['MerTradeNo'], $order_prefix);
            $order_ID = (int) apply_filters('ry_payuni_trade_no_to_order_id', $order_ID, $ipn_info['MerTradeNo']);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }
        return false;
    }
}
