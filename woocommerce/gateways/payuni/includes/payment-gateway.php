<?php

defined('ABSPATH') or exit;

abstract class RY_WT_WC_PAYUNi_Payment_Gateway extends RY_WT_WC_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct();
    }

    public function receipt_page($order_ID)
    {
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_PAYUNi_Gateway_Api::instance()->checkout_form($order, $this);
            WC()->cart->empty_cart();
        }
    }

    public function get_icon_url()
    {
        return apply_filters('ry_gateway_payuni_icon', RY_WT_PLUGIN_URL . 'assets/icons/payuni_logo.png');
    }

    public function get_icon()
    {
        $icon_html = '<img src="' . esc_attr($this->get_icon_url()) . '" alt="' . esc_attr__('PAYUNi', 'ry-woocommerce-tools') . '">'; // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }
}
