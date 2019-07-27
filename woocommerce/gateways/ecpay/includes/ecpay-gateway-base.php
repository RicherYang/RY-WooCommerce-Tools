<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Base extends WC_Payment_Gateway {
	public static $log_enabled = false;
	public static $log = false;

	public $inpay = false;

	public function __construct() {
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

		if( $this->enabled ) {
			add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
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
