<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Base extends WC_Payment_Gateway {
	public static $log_enabled = false;
	public static $log = false;

	public $inpay = false;

	public function __construct() {
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		if( $this->enabled ) {
			RY_ECPay_Gateway_Response::init($this->id);

			add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
		}
	}

	public function get_icon() {
		$icon_html = '<img src="' . esc_attr(RY_WT_PLUGIN_URL . 'icon/ecpay_logo.png') . '" alt="' . esc_attr__('ECPay', RY_WT::$textdomain) . '">';

		return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
	}

	public function process_admin_options() {
		$filed_name = 'woocommerce_' . $this->id . '_min_amount';
		$_POST[$filed_name] = (int) $_POST[$filed_name];
		if( $_POST[$filed_name] < 0 ) {
			$_POST[$filed_name] = 0;
			WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', RY_WT::$textdomain), $this->method_title));
		}

		parent::process_admin_options();
	}

	public function receipt_page($order_id) {
		if( $order = wc_get_order($order_id) ) {
			if( $this->inpay ) {
				RY_ECPay_Gateway_Api::inpay_checkout_form($order, $this);
			} else {
				RY_ECPay_Gateway_Api::checkout_form($order, $this);
			}
			WC()->cart->empty_cart();
		}
	}

	protected static function get_payment_subtypes() {
		return array(
			'TAISHIN' => __('TAISHIN', RY_WT::$textdomain),
			'ESUN' => __('ESUN', RY_WT::$textdomain),
			'BOT' => __('BOT', RY_WT::$textdomain),
			'FUBON' => __('FUBON', RY_WT::$textdomain),
			'CHINATRUST' => __('CHINATRUST', RY_WT::$textdomain),
			'FIRST' => __('FIRST', RY_WT::$textdomain),
			'CATHAY' => __('CATHAY', RY_WT::$textdomain),
			'MEGA' => __('MEGA', RY_WT::$textdomain),
			'LAND' => __('LAND', RY_WT::$textdomain),
			'TACHONG' => __('TACHONG', RY_WT::$textdomain),
			'SINOPAC' => __('SINOPAC', RY_WT::$textdomain),
			'CVS' => __('CVS', RY_WT::$textdomain),
			'OK' => __('OK', RY_WT::$textdomain),
			'FAMILY' => __('FAMILY', RY_WT::$textdomain),
			'HILIFE' => __('HILIFE', RY_WT::$textdomain),
			'IBON' => __('IBON', RY_WT::$textdomain)
		);
	}

	protected function check_inpay_with_ssl() {
		$post_filed = 'woocommerce_' . $this->id . '_inpay';
		if( isset($_POST[$post_filed]) && 1 == (int) $_POST[$post_filed] ) {
			if( 'yes' !== get_option('woocommerce_force_ssl_checkout') ) {
				unset($_POST[$post_filed]);
				WC_Admin_Settings::add_error(__('Inpay only work with ssl. You must enable force secure checkout.', RY_WT::$textdomain));
			}
		}
	}
}
