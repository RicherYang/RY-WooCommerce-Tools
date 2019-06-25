<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Barcode extends RY_ECPay_Gateway_Base {
	public $payment_type = 'BARCODE';

	public function __construct() {
		$this->id = 'ry_ecpay_barcode';
		$this->has_fields = false;
		$this->order_button_text = __('Pay via BARCODE', 'ry-woocommerce-tools');
		$this->method_title = __('ECPay BARCODE', 'ry-woocommerce-tools');
		$this->method_description = '';

		$this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-barcode.php');
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->expire_date = (int) $this->get_option('expire_date', 7);
		$this->min_amount = (int) $this->get_option('min_amount', 0);
		$this->max_amount = (int) $this->get_option('max_amount', 0);

		add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

		if( is_checkout() || is_view_order_page() ) {
			wp_enqueue_style('ry_wt_ecpay_shipping', RY_WT_PLUGIN_URL . 'style/ry_wt.css');
		}

		parent::__construct();
	}

	public function is_available() {
		if( 'yes' == $this->enabled && WC()->cart ) {
			$total = $this->get_order_total();

			if( $total > 0 ) {
				if( $total < 16 ) {
					return false;
				}
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
		$order->add_order_note(__('Pay via ECPay BARCODE', 'ry-woocommerce-tools'));
		wc_reduce_stock_levels($order_id);

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),
		];
	}

	public function process_admin_options() {
		$_POST['woocommerce_ry_ecpay_barcode_expire_date'] = (int) $_POST['woocommerce_ry_ecpay_barcode_expire_date'];
		if( $_POST['woocommerce_ry_ecpay_barcode_expire_date'] < 1 || $_POST['woocommerce_ry_ecpay_barcode_expire_date'] > 30 ) {
			$_POST['woocommerce_ry_ecpay_barcode_expire_date'] = 7;
			WC_Admin_Settings::add_error(__('BARCODE payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
		}

		$_POST['woocommerce_ry_ecpay_barcode_min_amount'] = (int) $_POST['woocommerce_ry_ecpay_barcode_min_amount'];
		if( $_POST['woocommerce_ry_ecpay_barcode_min_amount'] > 0 && $_POST['woocommerce_ry_ecpay_barcode_min_amount'] < 30 ) {
			$_POST['woocommerce_ry_ecpay_barcode_min_amount'] = 0;
			/* translators: %s: Gateway method title */
			WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
		}

		$_POST['woocommerce_ry_ecpay_barcode_max_amount'] = (int) $_POST['woocommerce_ry_ecpay_barcode_max_amount'];
		if( $_POST['woocommerce_ry_ecpay_barcode_max_amount'] > 20000 ) {
			/* translators: %s: Gateway method title */
			WC_Admin_Settings::add_message(sprintf(__('%s maximum amount more then ECPay normal maximum (20000).', 'ry-woocommerce-tools'), $this->method_title));
		}

		parent::process_admin_options();
	}

	public function admin_payment_info($order) {
		if( $order->get_payment_method() != 'ry_ecpay_barcode' ) {
			return;
		}
		$payment_type = $order->get_meta('_ecpay_payment_type');
		?>
		<h3 style="clear:both"><?=__('Payment details', 'ry-woocommerce-tools') ?></h3>
		<table>
			<tr>
				<td><?=__('Barcode 1', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_barcode_Barcode1') ?></td>
			</tr>
			<tr>
				<td><?=__('Barcode 2', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_barcode_Barcode2') ?></td>
			</tr>
			<tr>
				<td><?=__('Barcode 3', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_barcode_Barcode3') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_barcode_ExpireDate') ?></td>
			</tr>
		</table>
		<?php
	}
}
