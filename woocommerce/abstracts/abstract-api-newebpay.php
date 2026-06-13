<?php

defined('ABSPATH') or exit;

abstract class RY_WT_NewebPay_Api extends RY_WT_Api
{
    protected const ENCRYPT_METHOD = 'aes-256-cbc';

    protected function get_3rd_return_url($order = null)
    {
        $return_url = WC()->api_request_url('ry_newebpay_gateway_return');
        if ($order) {
            $return_url = add_query_arg('id', $order->get_id(), $return_url);
            $return_url = add_query_arg('key', $order->get_order_key(), $return_url);
        }

        return $return_url;
    }

    protected function generate_trade_no($order_ID, $order_prefix = '')
    {
        $trade_no = $this->pre_generate_trade_no($order_ID, $order_prefix);
        $trade_no = apply_filters('ry_newebpay_trade_no', $trade_no, $order_ID);
        return substr($trade_no, 0, 18);
    }

    protected function generate_hash_value(string|array $args, string $HashKey, string $HashIV)
    {
        if (is_array($args)) {
            $string = http_build_query([
                'Amt' => $args['Amt'],
                'MerchantID' => $args['MerchantID'],
                'MerchantOrderNo' => $args['MerchantOrderNo'],
            ]);
            $string = 'IV=' . $HashIV . '&' . $string . '&Key=' . $HashKey;
        } else {
            $string = 'HashKey=' . $HashKey . '&' . $args . '&HashIV=' . $HashIV;
        }
        $string = hash('sha256', $string);
        return strtoupper($string);
    }

    protected function args_encrypt($args, $HashKey, $HashIV)
    {
        ksort($args);
        $args_string = http_build_query($args);
        $encrypt_string = openssl_encrypt($args_string, self::ENCRYPT_METHOD, $HashKey, OPENSSL_RAW_DATA, $HashIV);

        return bin2hex($encrypt_string);
    }

    protected function args_decrypt($string, $HashKey, $HashIV)
    {
        $string = hex2bin($string);
        $decrypt_string = openssl_decrypt($string, self::ENCRYPT_METHOD, $HashKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $HashIV);

        $slast = ord(substr($decrypt_string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . '}/', $decrypt_string)) {
            return substr($decrypt_string, 0, strlen($decrypt_string) - $slast);
        } else {
            return false;
        }
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

    protected function get_info_value($ipn_info)
    {
        return $ipn_info['TradeInfo'] ?? false;
    }

    protected function get_hash_value($ipn_info)
    {
        return $ipn_info['TradeSha'] ?? false;
    }

    protected function get_status($ipn_info)
    {
        return $ipn_info->Status ?? false;
    }

    protected function get_status_msg($ipn_info)
    {
        return $ipn_info->Message ?? false;
    }

    protected function get_transaction_id($ipn_info)
    {
        return $ipn_info->Result->TradeNo ?? false;
    }

    protected function get_payment_type($ipn_info)
    {
        return $ipn_info->Result->PaymentType ?? false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info->Result->MerchantOrderNo)) {
            $order_ID = $this->trade_no_to_order_no($ipn_info->Result->MerchantOrderNo, $order_prefix);
            $order_ID = (int) apply_filters('ry_newebpay_trade_no_to_order_id', $order_ID, $ipn_info->Result->MerchantOrderNo);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }
        return false;
    }
}
