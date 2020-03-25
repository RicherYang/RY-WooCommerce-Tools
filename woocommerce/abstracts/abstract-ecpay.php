<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

if (!class_exists('RY_ECPay')) {
    abstract class RY_ECPay
    {
        protected static $do_die = false;

        protected static function generate_trade_no($order_id, $order_prefix = '')
        {
            $trade_no = $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
            $trade_no = substr($trade_no, 0, 20);
            $trade_no = apply_filters('ry_ecpay_trade_no', $trade_no);
            return substr($trade_no, 0, 20);
        }

        protected static function add_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
        {
            $args['CheckMacValue'] = self::generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args);
            return $args;
        }

        protected static function urlencode($string)
        {
            $string = str_replace(
                ['%2D', '%2d', '%5F', '%5f', '%2E', '%2e', '%2A', '%2a', '%21', '%28', '%29'],
                [  '-',   '-',   '_',   '_',    '.',  '.',   '*',   '*',   '!',   '(',   ')'],
                urlencode($string)
            );
            return $string;
        }

        protected static function generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
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

            $args_string = implode('&', $args_string);
            $args_string = self::urlencode($args_string);
            $args_string = strtolower($args_string);
            $check_value = hash($hash_algo, $args_string);
            $check_value = strtoupper($check_value);

            return $check_value;
        }

        protected static function link_server($post_url, $args)
        {
            wc_set_time_limit(40);

            $send_body = [];
            foreach ($args as $key => $value) {
                $send_body[] = $key . '=' . $value;
            }

            return wp_remote_post($post_url, [
                'timeout' => 20,
                'body' => implode('&', $send_body)
            ]);
        }

        protected static function get_check_value($ipn_info)
        {
            if (isset($ipn_info['CheckMacValue'])) {
                return $ipn_info['CheckMacValue'];
            }
            return false;
        }

        protected static function get_status($ipn_info)
        {
            if (isset($ipn_info['RtnCode'])) {
                return (int) $ipn_info['RtnCode'];
            }
            return false;
        }

        protected static function get_status_msg($ipn_info)
        {
            if (isset($ipn_info['RtnMsg'])) {
                return $ipn_info['RtnMsg'];
            }
            return false;
        }

        protected static function get_transaction_id($ipn_info)
        {
            if (isset($ipn_info['TradeNo'])) {
                return $ipn_info['TradeNo'];
            }
            return false;
        }

        protected static function get_order_id($ipn_info, $order_prefix = '')
        {
            if (isset($ipn_info['MerchantTradeNo'])) {
                $order_id = $ipn_info['MerchantTradeNo'];
                $order_id = substr($order_id, strlen($order_prefix), strrpos($order_id, 'TS'));
                $order_id = (int) $order_id;
                if ($order_id > 0) {
                    return $order_id;
                }
            }
            return false;
        }

        public static function set_do_die()
        {
            self::$do_die = true;
        }

        protected static function die_success()
        {
            if (self::$do_die) {
                die('1|OK');
            }
        }

        protected static function die_error()
        {
            if (self::$do_die) {
                die('0|');
            }
        }
    }
}
