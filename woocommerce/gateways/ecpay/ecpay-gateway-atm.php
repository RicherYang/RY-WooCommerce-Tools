<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Atm extends RY_ECPay_Gateway_Base {
	public $payment_type = 'ATM';
	public $inpay_payment_type = 'ATM';

	public function __construct() {
		$this->id = 'ry_ecpay_atm';
		$this->has_fields = false;
		$this->order_button_text = __('Pay via ATM', RY_WT::$textdomain);
		$this->method_title = __('ECPay ATM', RY_WT::$textdomain);
		$this->method_description = '';

		$this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-atm.php');
		$this->init_settings();

		$this->inpay = 'yes' == $this->get_option('inpay');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->expire_date = (int) $this->get_option('expire_date', 3);
		$this->min_amount = (int) $this->get_option('min_amount', 0);
		$this->max_amount = (int) $this->get_option('max_amount', 0);

		add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'admin_payment_info'));
		add_action('woocommerce_view_order', array($this, 'payment_info'), 9);
		add_action('woocommerce_thankyou', array($this, 'payment_info'), 9);

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

			if( $this->max_amount > 0 and $total > $this->max_amount ) {
				return false;
			}
		}

		return parent::is_available();
	}

	public function process_payment($order_id) {
		$order = wc_get_order($order_id);
		$order->add_order_note(__('Pay via ECPay ATM', RY_WT::$textdomain));
		wc_reduce_stock_levels($order_id);

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		);
	}

	public function process_admin_options() {
		$this->check_inpay_with_ssl();

		$_POST['woocommerce_ry_ecpay_atm_expire_date'] = (int) $_POST['woocommerce_ry_ecpay_atm_expire_date'];
		if( $_POST['woocommerce_ry_ecpay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_ecpay_atm_expire_date'] > 60 ) {
			$_POST['woocommerce_ry_ecpay_atm_expire_date'] = 3;
			WC_Admin_Settings::add_error(__('ATM payment deadline out of range. Set as default value.', RY_WT::$textdomain));
		}

		parent::process_admin_options();
	}

	public function admin_payment_info($order) {
		if( $order->get_payment_method() != 'ry_ecpay_atm' ) {
			return;
		}
		$payment_type = $order->get_meta('_ecpay_payment_type');
		?>
		<h3><?=__('Payment details', RY_WT::$textdomain) ?></h3>
		<table>
			<tr>
				<td><?=__('Bank', RY_WT::$textdomain) ?></td>
				<td><?=__($order->get_meta('_ecpay_payment_subtype'), RY_WT::$textdomain) ?> (<?=$order->get_meta('_ecpay_atm_BankCode') ?>)</td>
			</tr>
			<tr>
				<td><?=__('ATM Bank account', RY_WT::$textdomain) ?></td>
				<td><?=$order->get_meta('_ecpay_atm_vAccount') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', RY_WT::$textdomain) ?></td>
				<td><?=$order->get_meta('_ecpay_atm_ExpireDate') ?></td>
			</tr>
		</table>
		<?php
	}

	public function payment_info($order_id) {
		if( !$order_id ) {
			return;
		}

		$args = array(
			'order_id' => $order_id,
		);
		wc_get_template('order/order-ecpay-payment-info-atm.php', $args, '', RY_WT_PLUGIN_DIR . 'templates/');
	}
}
