<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Credit extends RY_ECPay_Gateway_Base {
	public $payment_type = 'Credit';
	public $inpay_payment_type = 'CREDIT';

	public function __construct() {
		$this->id = 'ry_ecpay_credit';
		$this->has_fields = false;
		$this->order_button_text = __('Pay via Credit', RY_WT::$textdomain);
		$this->method_title = __('ECPay Credit', RY_WT::$textdomain);
		$this->method_description = '';

		$this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-credit.php');
		$this->init_settings();

		$this->inpay = 'yes' == $this->get_option('inpay');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->min_amount = (int) $this->get_option('min_amount', 0);

		parent::__construct();
	}

	public function is_available() {
		if( 'yes' == $this->enabled && WC()->cart ) {
			$total = WC()->cart->get_displayed_subtotal();
			if( 'incl' === WC()->cart->tax_display_cart ) {
				$total = round($total - (WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total()), wc_get_price_decimals());
			} else {
				$total = round($total - WC()->cart->get_cart_discount_total(), wc_get_price_decimals());
			}

			if( $this->min_amount > 0 and $total < $this->min_amount ) {
				return false;
			}
		}

		return parent::is_available();
	}

	public function process_payment($order_id) {
		$order = wc_get_order($order_id);
		$order->add_order_note(__('Pay via ECPay Credit', RY_WT::$textdomain));
		wc_reduce_stock_levels($order_id);

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		);
	}

	public function process_admin_options() {
		$this->check_inpay_with_ssl();

		parent::process_admin_options();
	}
}
