<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_ECPay_Gateway {
	public static $log_enabled = false;
	public static $log = false;
	public static $log_source = 'ry_ecpay';

	public static function init() {
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-api.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-response.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-base.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-credit.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-credit-installment.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-webatm.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-atm.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-cvs.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-barcode.php');

		self::$log_enabled = 'yes' === get_option(RY_WT::$option_prefix . 'ecpay_gateway_log', 'no');

		add_filter('woocommerce_get_sections_rytools', array(__CLASS__, 'add_sections'));
		add_filter('woocommerce_get_settings_rytools', array(__CLASS__, 'add_setting'), 10, 2);
		add_action('woocommerce_update_options_rytools_ecpay_gateway', array(__CLASS__, 'check_option'));

		if( 'yes' === get_option(RY_WT::$option_prefix . 'ecpay_gateway', 'yes') ) {
			add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_method'));
		}
	}

	public static function log($message, $level = 'info') {
		if( self::$log_enabled ) {
			if( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log($level, $message, array('source' => 'ry_ecpay_gateway'));
		}
	}

	public static function add_sections($sections) {
		$sections['ecpay_gateway'] = __('ECPay gateway options', RY_WT::$textdomain);

$orders = wc_get_orders(array(
	'limit' => -1,
	'meta_query' => array(
		'relation' => 'AND',
		array(
			'key'     => '_shipping_cvs_info',
			'compare' => 'EXISTS',
		)
	)
));
var_dump(count($orders));
foreach( $orders as $order ) {
	
	$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
	//var_dump($cvs_info_list);
}

		return $sections;
	}

	public static function add_setting($settings, $current_section) {
		if( $current_section == 'ecpay_gateway' ) {
			$settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway.php');
		}
		return $settings;
	}

	public static function get_ecpay_api_info() {
		if( 'yes' === get_option(RY_WT::$option_prefix . 'ecpay_gateway_testmode', 'yes') ) {
			$MerchantID = '2000132';
			$HashKey = '5294y06JbISpM5x9';
			$HashIV = 'v77hoKGq4kWxNNIS';
		} else {
			$MerchantID = get_option(RY_WT::$option_prefix . 'ecpay_gateway_MerchantID');
			$HashKey = get_option(RY_WT::$option_prefix . 'ecpay_gateway_HashKey');
			$HashIV = get_option(RY_WT::$option_prefix . 'ecpay_gateway_HashIV');
		}

		return array($MerchantID, $HashKey, $HashIV);
	}

	public static function check_option() {
		if( 'yes' == get_option(RY_WT::$option_prefix . 'ecpay_gateway', 'yes') ) {
			$enable = true;
			if( 'yes' !== get_option(RY_WT::$option_prefix . 'ecpay_gateway_testmode', 'yes') ) {
				if( empty(get_option(RY_WT::$option_prefix . 'ecpay_gateway_MerchantID')) ) {
					$enable = false;
				}
				if( empty(get_option(RY_WT::$option_prefix . 'ecpay_gateway_HashKey')) ) {
					$enable = false;
				}
				if( empty(get_option(RY_WT::$option_prefix . 'ecpay_gateway_HashIV')) ) {
					$enable = false;
				}
			}
			if( !$enable ) {
				WC_Admin_Settings::add_error(__('ECPay gateway method failed to enable!', RY_WT::$textdomain));
				update_option(RY_WT::$option_prefix . 'ecpay_gateway', 'no');
			}
		}
		if( !preg_match('/^[a-z0-9]*$/i', get_option(RY_WT::$option_prefix . 'ecpay_gateway_order_prefix')) ) {
			WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed allowed', RY_WT::$textdomain));
			update_option(RY_WT::$option_prefix . 'ecpay_gateway_order_prefix', '');
		}
	}

	public static function add_method($methods) {
		$methods[] = 'RY_ECPay_Gateway_Credit';
		$methods[] = 'RY_ECPay_Gateway_Credit_Installment';
		$methods[] = 'RY_ECPay_Gateway_Webatm';
		$methods[] = 'RY_ECPay_Gateway_Atm';
		$methods[] = 'RY_ECPay_Gateway_Cvc';
		$methods[] = 'RY_ECPay_Gateway_Barcode';

		return $methods;
	}
}
