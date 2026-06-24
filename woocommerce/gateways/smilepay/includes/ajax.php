<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_SmilePay_Gateway_Ajax
{
    protected static ?self $_instance = null;

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
    }

    public function get_code()
    {
        check_ajax_referer('smilepay-getcode');

        $order_key = sanitize_locale_name($_POST['key'] ?? '');
        $order_ID = intval($_POST['id'] ?? '');
        $order = wc_get_order($order_ID);
        if ($order && hash_equals($order->get_order_key(), $order_key)) {
            $payment_method = $order->get_payment_method();
            $payment_gateways = WC()->payment_gateways()->payment_gateways();
            if (isset($payment_gateways[$payment_method])) {
                $gateway = $payment_gateways[$payment_method];
                if (is_object($gateway) && $gateway instanceof RY_WT_WC_SmilePay_Payment_Gateway) {
                    RY_WT_WC_SmilePay_Gateway_Api::instance()->get_code($order, $gateway);
                }
            }
            echo esc_url_raw($order->get_checkout_order_received_url());
        } else {
            echo esc_url_raw(home_url('/'));
        }

        wp_die();
    }
}
