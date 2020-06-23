<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_SmilePay_Gateway_Base extends WC_Payment_Gateway
{
    public static $log_enabled = false;
    public static $log = false;

    public $get_code_mode = false;

    public function __construct()
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if ($this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }
    }

    public function get_icon()
    {
        $icon_html = '<img src="' . esc_attr(RY_WT_PLUGIN_URL . 'icon/smilepay_logo.png') . '" alt="' . esc_attr__('SmilePay', 'ry-woocommerce-tools') . '">';

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
            wc_enqueue_js(
                '
$.blockUI({
    message: "' . __('Please wait.<br>Getting checkout info.', 'ry-woocommerce-tools') . '",
    baseZ: 99999,
    overlayCSS: {
        background: "#000",
        opacity: 0.4
    },
    css: {
        "font-size": "1.5em",
        padding: "1.5em",
        textAlign: "center",
        border: "3px solid #aaa",
        backgroundColor: "#fff",
    }
});
$.ajax({
    type: "GET",
    url: wc_checkout_params.ajax_url,
    data: {
        action: "RY_SmilePay_getcode",
        id: ' . $order->get_id() . '
    },
    dataType: "text",
    success: function(result) {
        window.location = result;
    }
 });'
            );
            WC()->cart->empty_cart();
        }
    }
}
