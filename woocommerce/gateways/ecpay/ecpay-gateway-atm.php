<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Atm extends RY_ECPay_Gateway_Base {
	public $payment_type = 'ATM';
	public $inpay_payment_type = 'ATM';

	public function __construct() {
		$this->id = 'ry_ecpay_atm';
		$this->has_fields = false;
		$this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
		$this->method_title = __('ECPay ATM', 'ry-woocommerce-tools');
		$this->method_description = '';

		$this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-atm.php');
		$this->init_settings();

		$this->inpay = 'yes' == $this->get_option('inpay');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->expire_date = (int) $this->get_option('expire_date', 3);
		$this->min_amount = (int) $this->get_option('min_amount', 0);
		$this->max_amount = (int) $this->get_option('max_amount', 0);

		add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

		parent::__construct();
	}

	public function is_available() {
		if( 'yes' == $this->enabled && WC()->cart ) {
			$total = $this->get_order_total();

			if( $total > 0 ) {
				if( $this->min_amount > 0 and $total < $this->min_amount ) {
					return false;
				}
				if( $this->max_amount > 0 and $total > $this->max_amount ) {
					return false;
				}
			}
		}

		return parent::is_available();
	}

	public function process_payment($order_id) {
		$order = wc_get_order($order_id);
		$order->add_order_note(__('Pay via ECPay ATM', 'ry-woocommerce-tools'));
		wc_reduce_stock_levels($order_id);

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		];
	}

	public function process_admin_options() {
		$this->check_inpay_with_ssl();

		$_POST['woocommerce_ry_ecpay_atm_expire_date'] = (int) $_POST['woocommerce_ry_ecpay_atm_expire_date'];
		if( $_POST['woocommerce_ry_ecpay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_ecpay_atm_expire_date'] > 60 ) {
			$_POST['woocommerce_ry_ecpay_atm_expire_date'] = 3;
			WC_Admin_Settings::add_error(__('ATM payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
		}

		if( $_POST['woocommerce_ry_ecpay_atm_min_amount'] > 0 && $_POST['woocommerce_ry_ecpay_atm_min_amount'] < 5 ) {
			$_POST['woocommerce_ry_ecpay_atm_min_amount'] = 0;
			/* translators: %s: Gateway method title */
			WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
		}

		parent::process_admin_options();
	}

	public function admin_payment_info($order) {
		if( $order->get_payment_method() != 'ry_ecpay_atm' ) {
			return;
		}
		$payment_type = $order->get_meta('_ecpay_payment_type');
		?>
		<h3 style="clear:both"><?=__('Payment details', 'ry-woocommerce-tools') ?></h3>
		<table>
			<tr>
				<td><?=__('Bank', 'ry-woocommerce-tools') ?></td>
				<td><?=__($order->get_meta('_ecpay_payment_subtype'), 'ry-woocommerce-tools') ?> (<?=$order->get_meta('_ecpay_atm_BankCode') ?>)</td>
			</tr>
			<tr>
				<td><?=__('ATM Bank account', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_atm_vAccount') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_atm_ExpireDate') ?></td>
			</tr>
		</table>
		<?php
	}
}
