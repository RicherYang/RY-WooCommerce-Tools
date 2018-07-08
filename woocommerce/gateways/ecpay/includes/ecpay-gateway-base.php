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
		$icon_html = '<img src="' . esc_attr(RY_WT_PLUGIN_URL . 'icon/ecpay_logo.png') . '" alt="' . esc_attr__('ECPay', 'ry-woocommerce-tools') . '">';

		return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
	}

	public function process_admin_options() {
		$filed_name = 'woocommerce_' . $this->id . '_min_amount';
		$_POST[$filed_name] = (int) $_POST[$filed_name];
		if( $_POST[$filed_name] < 0 ) {
			$_POST[$filed_name] = 0;
			WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
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
			'TAISHIN' => __('TAISHIN', 'ry-woocommerce-tools'),
			'ESUN' => __('ESUN', 'ry-woocommerce-tools'),
			'BOT' => __('BOT', 'ry-woocommerce-tools'),
			'FUBON' => __('FUBON', 'ry-woocommerce-tools'),
			'CHINATRUST' => __('CHINATRUST', 'ry-woocommerce-tools'),
			'FIRST' => __('FIRST', 'ry-woocommerce-tools'),
			'CATHAY' => __('CATHAY', 'ry-woocommerce-tools'),
			'MEGA' => __('MEGA', 'ry-woocommerce-tools'),
			'LAND' => __('LAND', 'ry-woocommerce-tools'),
			'TACHONG' => __('TACHONG', 'ry-woocommerce-tools'),
			'SINOPAC' => __('SINOPAC', 'ry-woocommerce-tools'),
			'CVS' => __('CVS', 'ry-woocommerce-tools'),
			'OK' => __('OK', 'ry-woocommerce-tools'),
			'FAMILY' => __('FAMILY', 'ry-woocommerce-tools'),
			'HILIFE' => __('HILIFE', 'ry-woocommerce-tools'),
			'IBON' => __('IBON', 'ry-woocommerce-tools')
		);
	}

	protected function check_inpay_with_ssl() {
		$post_filed = 'woocommerce_' . $this->id . '_inpay';
		if( isset($_POST[$post_filed]) && 1 == (int) $_POST[$post_filed] ) {
			if( !wc_checkout_is_https() ) {
				unset($_POST[$post_filed]);
				WC_Admin_Settings::add_error(__('Inpay only work with ssl. You must enable force secure checkout.', 'ry-woocommerce-tools'));
			}
		}
	}
}
