<?php

abstract class RY_WT_Api
{
    protected $do_die = false;

    protected function pre_generate_trade_no($order_ID, $order_prefix = '')
    {
        return $order_prefix . $order_ID . 'TS' . random_int(0, 9) . strrev((string) time());
    }

    protected function trade_no_to_order_no($trade_no, $order_prefix = '')
    {
        return (int) substr($trade_no, strlen($order_prefix), strrpos($trade_no, 'TS'));
    }

    protected function get_item_name($item_name, $order)
    {
        if (empty($item_name)) {
            $items = $order->get_items();
            if (count($items)) {
                $item = reset($items);
                $item_name = trim($item->get_name());
            }
        }
        return str_replace(['^', '\'', '`', '!', '@', '＠', '#', '%', '&', '*', '+', '\\', '"', '<', '>', '|', '_', '[', ']'], '', $item_name);
    }

    public function gateway_return()
    {
        $order_key = wp_unslash($_GET['key'] ?? '');
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $order = wc_get_order($order_ID);
        if ($order && hash_equals($order->get_order_key(), $order_key)) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());
        }

        $return_url = apply_filters('woocommerce_get_return_url', $return_url, $order);
        wp_redirect($return_url);

        exit();
    }

    protected function submit_sctipt($action_script, $order)
    {
        $blockUI = '$.blockUI({
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
        });';

        wc_enqueue_js($blockUI . ' setTimeout(function() { ' . $action_script . ' }, 100);');
    }

    public function set_do_die()
    {
        $this->do_die = true;
    }

    public function set_not_do_die()
    {
        $this->do_die = false;
    }

    protected function die_success()
    {
        if ($this->do_die) {
            exit('1|OK');
        }
    }

    protected function die_error()
    {
        if ($this->do_die) {
            exit('0|');
        }
    }
}
