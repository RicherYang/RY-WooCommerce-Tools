<?php

abstract class RY_WT_WC_ECPay_Payment_Gateway extends RY_WT_WC_Payment_Gateway
{
    protected $process_payment_note;

    public function __construct()
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if ($this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }

        parent::__construct();
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
    }

    public function receipt_page($order_ID)
    {
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_ECPay_Gateway_Api::instance()->checkout_form($order, $this);
            WC()->cart->empty_cart();
        }
    }

    public function process_payment($order_ID)
    {
        $order = wc_get_order($order_ID);
        $order->add_order_note($this->process_payment_note);
        wc_maybe_reduce_stock_levels($order_ID);
        wc_release_stock_for_order($order);

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function get_icon_url()
    {
        return apply_filters('ry_gateway_ecpay_icon', RY_WT_PLUGIN_URL . 'assets/icons/ecpay_logo.png');
    }

    public function get_icon()
    {
        $icon_html = '<img src="' . esc_attr($this->get_icon_url()) . '" alt="' . esc_attr__('ECPay', 'ry-woocommerce-tools') . '">';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }
}
