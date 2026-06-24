<?php

defined('ABSPATH') or exit;

abstract class RY_WT_Gateway_Model extends RY_WT_Model
{
    public function payment_info($order_ID)
    {
        if (!$order_ID) {
            return;
        }
        if (!$order = wc_get_order($order_ID)) {
            return;
        }

        $payment_method = $order->get_payment_method();
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        if (!isset($payment_gateways[$payment_method])) {
            return;
        }

        $gateway = $payment_gateways[$payment_method];
        if (defined(get_class($gateway) . '::INFO_TEMPLATE') && $gateway::INFO_TEMPLATE) {
            if (file_exists(RY_WT_PLUGIN_DIR . 'templates/order/' . $gateway::INFO_TEMPLATE)) {
                $args = [
                    'order' => $order,
                ];
                wc_get_template('order/' . $gateway::INFO_TEMPLATE, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
            }
        }
    }
}
