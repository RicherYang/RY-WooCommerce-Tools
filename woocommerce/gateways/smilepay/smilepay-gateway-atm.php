<?php
class RY_SmilePay_Gateway_Atm extends RY_SmilePay_Gateway_Base
{
    public $payment_type = 2;

    protected $check_min_amount = 13;

    public function __construct()
    {
        $this->id = 'ry_smilepay_atm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay ATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->get_code_mode = true;

        $this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings-smilepay-gateway-atm.php');
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 7);
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
                if ($total < 12) {
                    return false;
                }
                if ($this->min_amount > 0 and $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 and $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via SmilePay ATM', 'ry-woocommerce-tools'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function process_admin_options()
    {
        $_POST['woocommerce_ry_smilepay_atm_expire_date'] = (int) $_POST['woocommerce_ry_smilepay_atm_expire_date'];
        if ($_POST['woocommerce_ry_smilepay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_smilepay_atm_expire_date'] > 30) {
            $_POST['woocommerce_ry_smilepay_atm_expire_date'] = 7;
            WC_Admin_Settings::add_error(__('ATM payment deadline out of range. Set as default value.', 'ry-woocommerce-tools'));
        }

        $_POST['woocommerce_ry_smilepay_atm_max_amount'] = (int) $_POST['woocommerce_ry_smilepay_atm_max_amount'];
        if ($_POST['woocommerce_ry_smilepay_atm_max_amount'] > 30000) {
            /* translators: %1$s: Gateway method title, %2$d normal maximum */
            WC_Admin_Settings::add_message(sprintf(__('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'), $this->method_title, 20000));
        }

        parent::process_admin_options();
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() != 'ry_smilepay_atm') {
            return;
        } ?>
<h3 style="clear:both"><?=__('Payment details', 'ry-woocommerce-tools') ?>
</h3>
<table>
    <tr>
        <td><?=__('Bank', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=_x($order->get_meta('_smilepay_atm_BankCode'), 'Bank code', 'ry-woocommerce-tools') ?> (<?=$order->get_meta('_smilepay_atm_BankCode') ?>)</td>
    </tr>
    <tr>
        <td><?=__('ATM Bank account', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_smilepay_atm_vAccount') ?>
        </td>
    </tr>
    <tr>
        <td><?=__('Payment deadline', 'ry-woocommerce-tools') ?>
        </td>
        <td><?=$order->get_meta('_smilepay_atm_ExpireDate') ?>
        </td>
    </tr>
</table>
<?php
    }
}
