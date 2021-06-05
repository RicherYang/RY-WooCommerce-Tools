<?php
class RY_NewebPay_Gateway_Base extends WC_Payment_Gateway
{
    public static $log_enabled = false;
    public static $log = false;

    public function __construct()
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if ($this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }
    }

    public function get_icon()
    {
        $icon_html = '<img src="' . esc_attr(RY_WT_PLUGIN_URL . 'icon/newebpay_logo.png') . '" alt="' . esc_attr__('NewebPay', 'ry-woocommerce-tools') . '">';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    public function process_admin_options()
    {
        $filed_name = 'woocommerce_' . $this->id . '_min_amount';
        $_POST[$filed_name] = (int) $_POST[$filed_name];
        if ($_POST[$filed_name] < 0) {
            $_POST[$filed_name] = 0;
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
        }

        parent::process_admin_options();
    }

    public function receipt_page($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            RY_NewebPay_Gateway_Api::checkout_form($order, $this);

            WC()->cart->empty_cart();
        }
    }
}
