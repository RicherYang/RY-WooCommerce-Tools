<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

abstract class RY_NewebPay {
	protected static $do_die = false;

	protected static function generate_trade_no($order_id, $order_prefix = '') {
		$trade_no = $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
		$trade_no = substr($trade_no, 0, 30);
		$trade_no = apply_filters('ry_newebpay_trade_no', $trade_no);
		return substr($trade_no, 0, 30);
	}

	protected static function args_encrypt($args, $HashKey, $HashIV) {
		ksort($args);

		$args_string = http_build_query($args);

		$pad = 32 - (strlen($args_string) % 32);
      $args_string .= str_repeat(chr($pad), $pad);

		if( function_exists('openssl_encrypt') ) {
			$encrypt_string = openssl_encrypt($args_string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $HashIV);
		}
		$encrypt_string = trim(bin2hex($encrypt_string));

		return $encrypt_string;
	}

	protected static function args_decrypt($string, $HashKey, $HashIV) {
		$string = hex2bin($string);

		if( function_exists('openssl_decrypt') ) {
			$decrypt_string = openssl_decrypt($string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $HashIV);
		}

		$slast = ord(substr($decrypt_string, -1));
		$slastc = chr($slast);
		if( preg_match("/$slastc{" . $slast . "}/", $decrypt_string) ) {
			$decrypt_string = substr($decrypt_string, 0, strlen($decrypt_string) - $slast);
			return $decrypt_string;
		} else {
			return false;
		}
	}

	protected static function generate_hash_value($string, $HashKey, $HashIV) {
		$string = 'HashKey=' . $HashKey
			. '&' . $string
			. '&HashIV=' . $HashIV;
		$string = hash('sha256', $string);
		$string = strtoupper($string);

		return $string;
	}

	protected static function get_tradeInfo_value($ipn_info) {
		if( isset($ipn_info['TradeInfo']) ) {
			return $ipn_info['TradeInfo'];
		}
		return false;
	}

	protected static function get_tradeSha_value($ipn_info) {
		if( isset($ipn_info['TradeSha']) ) {
			return $ipn_info['TradeSha'];
		}
		return false;
	}

	protected static function get_status($ipn_info) {
		if( isset($ipn_info->Status) ) {
			return $ipn_info->Status;
		}
		return false;
	}

	protected static function get_status_msg($ipn_info) {
		if( isset($ipn_info->Message) ) {
			return $ipn_info->Message;
		}
		return false;
	}

	protected static function get_transaction_id($ipn_info) {
		if( isset($ipn_info->Result->TradeNo) ) {
			return $ipn_info->Result->TradeNo;
		}
		return false;
	}

	protected static function get_payment_type($ipn_info) {
		if( isset($ipn_info->Result->PaymentType) ) {
			return $ipn_info->Result->PaymentType;
		}
		return false;
	}

	protected static function get_order_id($ipn_info, $order_prefix = '') {
		if( isset($ipn_info->Result->MerchantOrderNo) ) {
			$order_id = $ipn_info->Result->MerchantOrderNo;
			$order_id = substr($order_id, strlen($order_prefix), strrpos($order_id, 'TS'));
			$order_id = (int) $order_id;
			if( $order_id > 0 ) {
				return $order_id;
			}
		}
		return false;
	}

	protected static function set_do_die() {
		self::$do_die = true;
	}

	protected static function die_success() {
		if( self::$do_die ) {
			die('1|OK');
		}
	}

	protected static function die_error() {
		if( self::$do_die ) {
			die('1|OK');
		}
	}
}
