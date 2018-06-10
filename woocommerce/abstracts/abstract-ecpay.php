<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

abstract class RY_ECPay {
	protected static function generate_trade_no($order_id, $order_prefix = '') {
		$trade_no = $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
		$trade_no = substr($trade_no, 0, 20);
		$trade_no = apply_filters('ry_ecpay_trade_no', $trade_no);
		return substr($trade_no, 0, 20);
	}

	protected static function add_check_value($args, $HashKey, $HashIV, $hash_algo) {
		$args['CheckMacValue'] = self::generate_check_value($args, $HashKey, $HashIV, $hash_algo);
		return $args;
	}

	protected static function generate_check_value($args, $HashKey, $HashIV, $hash_algo) {
		if( isset($args['CheckMacValue']) ) {
			unset($args['CheckMacValue']);
		}

		ksort($args, SORT_STRING | SORT_FLAG_CASE);

		$args_string = array();
		$args_string[] = 'HashKey=' . $HashKey;
		foreach( $args as $key => $value ) {
			$args_string[] = $key . '=' . $value;
		}
		$args_string[] = 'HashIV=' . $HashIV;

		$args_string = implode('&', $args_string);
		$args_string = strtolower(urlencode($args_string));
		$args_string = str_replace(
			array('%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'),
			array('-', '_', '.', '!', '*', '(', ')'),
			$args_string
		);
		$check_value = hash($hash_algo, $args_string);
		$check_value = strtoupper($check_value);

		return $check_value;
	}

	protected static function get_check_value($ipn_info) {
		if( isset($ipn_info['CheckMacValue']) ) {
			return $ipn_info['CheckMacValue'];
		}
		return false;
	}

	protected static function get_status($ipn_info) {
		if( isset($ipn_info['RtnCode']) ) {
			return (int) $ipn_info['RtnCode'];
		}
		return false;
	}

	protected static function get_status_msg($ipn_info) {
		if( isset($ipn_info['RtnMsg']) ) {
			return $ipn_info['RtnMsg'];
		}
		return false;
	}

	protected static function get_transaction_id($ipn_info) {
		if( isset($ipn_info['TradeNo']) ) {
			return $ipn_info['TradeNo'];
		}
		return false;
	}

	protected static function get_order_id($ipn_info, $order_prefix = '') {
		if( isset($ipn_info['MerchantTradeNo']) ) {
			$order_id = substr($ipn_info['MerchantTradeNo'], 0, 20);
			$order_id = substr($order_id, strlen($order_prefix), strrpos($order_id, 'TS'));
			$order_id = (int) $order_id;
			if( $order_id > 0 ) {
				return $order_id;
			}
		}
		return false;
	}

	protected static function die_success() {
		die('1|OK');
	}

	protected static function die_error() {
		die('0|');
	}
}
