<?php
class RY_ECPay_Gateway_Atm extends RY_ECPay_Gateway_Base
{
    public $payment_type = 'ATM';

    protected $check_min_amount = 5;

    public function __construct()
    {
        $this->id = 'ry_ecpay_atm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay ATM', 'ry-woocommerce-tools');
        $this->method_description = '';

        $this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-atm.php');
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 3);
        $this->min_amount = (int) $this->get_option('min_amount', $this->check_min_amount);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
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
        $order->add_order_note(__('Pay via ECPay ATM', 'ry-woocommerce-tools'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_ry_ecpay_atm_expire_date'])) {
            $_POST['woocommerce_ry_ecpay_atm_expire_date'] = (int) $_POST['woocommerce_ry_ecpay_atm_expire_date'];
            if ($_POST['woocommerce_ry_ecpay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_ecpay_atm_expire_date'] > 60) {
                $_POST['woocommerce_ry_ecpay_atm_expire_date'] = 3;
                WC_Admin_Settings::add_error(__('ATM payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $_POST['woocommerce_ry_ecpay_atm_expire_date'] = 3;
        }

        parent::process_admin_options();
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() != 'ry_ecpay_atm') {
            return;
        }
        $payment_type = $order->get_meta('_ecpay_payment_type'); ?>
<h3 style="clear:both"><?php esc_html_e('Payment details', 'ry-woocommerce-tools') ?>
</h3>
<table>
    <tr>
        <td><?php esc_html_e('Bank', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=_x($order->get_meta('_ecpay_atm_BankCode'), 'Bank code', 'ry-woocommerce-tools') ?> (<?php echo esc_html($order->get_meta('_ecpay_atm_BankCode')); ?>)</td>
    </tr>
    <tr>
        <td><?php esc_html_e('ATM Bank account', 'ry-woocommerce-tools') ?>
        </td>
        <td><?php echo esc_html($order->get_meta('_ecpay_atm_vAccount')); ?>
        </td>
    </tr>
    <tr>
        <td><?php esc_html_e('Payment deadline', 'ry-woocommerce-tools') ?>
        </td>
        <td><?php echo esc_html($order->get_meta('_ecpay_atm_ExpireDate')); ?>
        </td>
    </tr>
</table>
<?php
    }
}
?>
