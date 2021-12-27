<?php
final class RY_Gateway
{
    public static function init()
    {
        add_action('woocommerce_api_ry_gateway_return', [__CLASS__, 'gateway_return']);
    }

    public static function gateway_return()
    {
        $order = null;
        $return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());

        if (isset($_GET['id'], $_GET['key'])) {
            $order_key = wc_clean(wp_unslash($_GET['key']));
            $order = wc_get_order((int) $_GET['id']);

            if ($order && hash_equals($order->get_order_key(), $order_key)) {
                $return_url = $order->get_checkout_order_received_url();
            }
        }

        $return_url = apply_filters('woocommerce_get_return_url', $return_url, $order);
        wp_redirect($return_url);
        die();
    }
}

RY_Gateway::init();
