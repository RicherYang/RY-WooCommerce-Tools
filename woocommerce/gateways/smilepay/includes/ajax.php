<?php

final class RY_WT_WC_SmilePay_Gateway_Ajax
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_SmilePay_Gateway_Ajax
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('wp_ajax_RY_SmilePay_getcode', [$this, 'get_code']);
        add_action('wp_ajax_nopriv_RY_SmilePay_getcode', [$this, 'get_code']);
        add_action('wp_ajax_RY_SmilePay_shipping_getcode', [$this, 'shipping_get_code']);
        add_action('wp_ajax_nopriv_RY_SmilePay_shipping_getcode', [$this, 'shipping_get_code']);
    }

    public function get_code()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $order = wc_get_order($order_ID);
        $url = false;
        if ($order) {
            $url = RY_WT_WC_SmilePay_Gateway_Api::instance()->get_code($order);
        }
        if (!$url) {
            $url = $order->get_checkout_order_received_url();
        }
        echo($url);

        wp_die();
    }

    public function shipping_get_code()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $order = wc_get_order($order_ID);
        $url = false;
        if ($order) {
            $url = RY_WT_WC_SmilePay_Shipping_Api::instance()->get_csv_info($order);
        }
        if (!$url) {
            $url = $order->get_checkout_order_received_url();
        }
        echo($url);

        wp_die();
    }
}
