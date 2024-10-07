<?php

abstract class RY_WT_NewebPay_Api extends RY_WT_Api
{
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

    protected function args_encrypt($args, $HashKey, $HashIV)
    {
        ksort($args);
        $args_string = http_build_query($args);
        $encrypt_string = openssl_encrypt($args_string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA, $HashIV);

        return bin2hex($encrypt_string);
    }

    protected function args_decrypt($string, $HashKey, $HashIV)
    {
        $string = hex2bin($string);
        $decrypt_string = openssl_decrypt($string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $HashIV);

        $slast = ord(substr($decrypt_string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . '}/', $decrypt_string)) {
            return substr($decrypt_string, 0, strlen($decrypt_string) - $slast);
        } else {
            return false;
        }
    }

    protected function generate_hash_value($string, $HashKey, $HashIV)
    {
        $string = 'HashKey=' . $HashKey . '&' . $string . '&HashIV=' . $HashIV;
        $string = hash('sha256', $string);
        return strtoupper($string);
    }

    protected function get_tradeInfo_value($ipn_info)
    {
        if (isset($ipn_info['TradeInfo'])) {
            return $ipn_info['TradeInfo'];
        }
        return false;
    }

    protected function get_tradeSha_value($ipn_info)
    {
        if (isset($ipn_info['TradeSha'])) {
            return $ipn_info['TradeSha'];
        }
        return false;
    }

    protected function get_status($ipn_info)
    {
        if (isset($ipn_info->Status)) {
            return $ipn_info->Status;
        }
        return false;
    }

    protected function get_status_msg($ipn_info)
    {
        if (isset($ipn_info->Message)) {
            return $ipn_info->Message;
        }
        return false;
    }

    protected function get_transaction_id($ipn_info)
    {
        if (isset($ipn_info->Result->TradeNo)) {
            return $ipn_info->Result->TradeNo;
        }
        return false;
    }

    protected function get_payment_type($ipn_info)
    {
        if (isset($ipn_info->Result->PaymentType)) {
            return $ipn_info->Result->PaymentType;
        }
        return false;
    }

    protected function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info->Result->MerchantOrderNo)) {
            $order_ID = $this->trade_no_to_order_no($ipn_info->Result->MerchantOrderNo, $order_prefix);
            $order_ID = apply_filters('ry_newebpay_trade_no_to_order_id', $order_ID, $ipn_info->Result->MerchantOrderNo);
            if ($order_ID > 0) {
                return $order_ID;
            }
        }
        return false;
    }
}
