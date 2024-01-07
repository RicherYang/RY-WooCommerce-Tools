<?php

class RY_SmilePay_Gateway_Atm extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const Payment_Type = 2;

    protected $check_min_amount = 13;
    protected $check_max_amount = 20000;

    public function __construct()
    {
        $this->id = 'ry_smilepay_atm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay ATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay ATM', 'ry-woocommerce-tools');
        $this->get_code_mode = true;

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/atm.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 3);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);

        parent::__construct();
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_ry_smilepay_atm_expire_date'])) {
            $_POST['woocommerce_ry_smilepay_atm_expire_date'] = (int) $_POST['woocommerce_ry_smilepay_atm_expire_date'];
            if ($_POST['woocommerce_ry_smilepay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_smilepay_atm_expire_date'] > 60) {
                $_POST['woocommerce_ry_smilepay_atm_expire_date'] = 3;
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $_POST['woocommerce_ry_smilepay_atm_expire_date'] = 3;
        }

        parent::process_admin_options();
    }

    public function admin_payment_info($order)
    {
        if ($this->id !== $order->get_payment_method()) {
            return;
        }
        ?>
<h3 style="clear:both"><?php esc_html_e('Payment details', 'ry-woocommerce-tools'); ?>
</h3>
<table>
    <tr>
        <td><?php esc_html_e('Bank', 'ry-woocommerce-tools'); ?>
        </td>
        <td><?=_x($order->get_meta('_smilepay_atm_BankCode'), 'Bank code', 'ry-woocommerce-tools'); ?> (<?php echo esc_html($order->get_meta('_smilepay_atm_BankCode')); ?>)</td>
    </tr>
    <tr>
        <td><?php esc_html_e('ATM Bank account', 'ry-woocommerce-tools'); ?>
        </td>
        <td><?php echo esc_html($order->get_meta('_smilepay_atm_vAccount')); ?>
        </td>
    </tr>
    <tr>
        <td><?php esc_html_e('Payment deadline', 'ry-woocommerce-tools'); ?>
        </td>
        <td><?php echo esc_html($order->get_meta('_smilepay_atm_ExpireDate')); ?>
        </td>
    </tr>
</table>
<?php
    }
}
?>
