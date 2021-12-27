<?php
class RY_NewebPay_Gateway_Barcode extends RY_NewebPay_Gateway_Base
{
    public $payment_type = 'BARCODE';

    protected $check_min_amount = 20;

    public function __construct()
    {
        $this->id = 'ry_newebpay_barcode';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via BARCODE', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay BARCODE', 'ry-woocommerce-tools');
        $this->method_description = '';

        $this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway-barcode.php');
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 7);
        $this->min_amount = (int) $this->get_option('min_amount', $this->check_min_amount);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

        if (is_checkout() || is_view_order_page()) {
            wp_enqueue_style('ry_wt_newebpay_shipping', RY_WT_PLUGIN_URL . 'style/ry_wt.css');
        }

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($total < 20) {
                    return false;
                }
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 && $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via NewebPay BARCODE', 'ry-woocommerce-tools'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function process_admin_options()
    {
        $_POST['woocommerce_ry_newebpay_barcode_expire_date'] = (int) $_POST['woocommerce_ry_newebpay_barcode_expire_date'];
        if ($_POST['woocommerce_ry_newebpay_barcode_expire_date'] < 1 || $_POST['woocommerce_ry_newebpay_barcode_expire_date'] > 180) {
            $_POST['woocommerce_ry_newebpay_barcode_expire_date'] = 7;
            WC_Admin_Settings::add_error(__('BARCODE payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
        }

        $_POST['woocommerce_ry_newebpay_barcode_max_amount'] = (int) $_POST['woocommerce_ry_newebpay_barcode_max_amount'];
        if ($_POST['woocommerce_ry_newebpay_barcode_max_amount'] > 40000) {
            /* translators: %1$s: Gateway method title, %2$d normal maximum */
            WC_Admin_Settings::add_message(sprintf(__('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'), $this->method_title, 40000));
        }

        parent::process_admin_options();
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() != 'ry_newebpay_barcode') {
            return;
        }
        $payment_type = $order->get_meta('_newebpay_payment_type'); ?>
<h3 style="clear:both"><?=__('Payment details', 'ry-woocommerce-tools') ?>
</h3>
<table>
    <tr>
        <td><?=__('Barcode 1', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_newebpay_barcode_Barcode1') ?>
        </td>
    </tr>
    <tr>
        <td><?=__('Barcode 2', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_newebpay_barcode_Barcode2') ?>
        </td>
    </tr>
    <tr>
        <td><?=__('Barcode 3', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_newebpay_barcode_Barcode3') ?>
        </td>
    </tr>
    <tr>
        <td><?=__('Payment deadline', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_newebpay_barcode_ExpireDate') ?>
        </td>
    </tr>
</table>
<?php
    }
}
