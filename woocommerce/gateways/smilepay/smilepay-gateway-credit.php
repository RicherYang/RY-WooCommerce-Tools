<?php
class RY_SmilePay_Gateway_Credit extends RY_SmilePay_Gateway_Base
{
    public $payment_type = 1;

    public function __construct()
    {
        $this->id = 'ry_smilepay_credit';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay Credit', 'ry-woocommerce-tools');
        $this->method_description = '';

        $this->form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings-smilepay-gateway-credit.php');
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', 0);

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
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via SmilePay Credit', 'ry-woocommerce-tools'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function process_admin_options()
    {
        $_POST['woocommerce_ry_smilepay_atm_min_amount'] = (int) $_POST['woocommerce_ry_smilepay_atm_min_amount'];
        if ($_POST['woocommerce_ry_smilepay_atm_min_amount'] > 0 && $_POST['woocommerce_ry_smilepay_atm_min_amount'] < 13) {
            $_POST['woocommerce_ry_smilepay_atm_min_amount'] = 0;
            /* translators: %s: Gateway method title */
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
        }

        parent::process_admin_options();
    }
}
