<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

if (!class_exists('RY_SmilePay')) {
    abstract class RY_SmilePay
    {
        protected static function generate_trade_no($order_id, $order_prefix = '')
        {
            $trade_no = $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
            $trade_no = substr($trade_no, 0, 20);
            $trade_no = apply_filters('ry_smilepay_trade_no', $trade_no);
            return substr($trade_no, 0, 20);
        }

        protected static function link_server($post_url, $args)
        {
            wc_set_time_limit(40);

            return wp_remote_post($post_url . '?' . http_build_query($args, '', '&'), [
                'timeout' => 20
            ]);
        }

        protected static function get_transaction_id($ipn_info)
        {
            if (isset($ipn_info->SmilePayNO)) {
                return (string) $ipn_info->SmilePayNO;
            }
            if (isset($ipn_info['Smseid'])) {
                return $ipn_info['Smseid'];
            }
            return false;
        }

        protected static function get_check_value($ipn_info)
        {
            if (isset($ipn_info['Mid_smilepay'])) {
                return $ipn_info['Mid_smilepay'];
            }
            return false;
        }

        protected static function get_order_id($ipn_info, $order_prefix = '')
        {
            if (isset($ipn_info['Data_id'])) {
                $order_id = $ipn_info['Data_id'];
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
            die('<Roturlstatus>RY_SmilePay</Roturlstatus>');
        }

        protected static function die_error()
        {
            die('0|');
        }
    }
}
