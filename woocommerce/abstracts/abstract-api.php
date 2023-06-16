<?php

abstract class RY_Abstract_Api
{
    protected static $do_die = false;

    protected static function pre_generate_trade_no($order_id, $order_prefix = '')
    {
        return $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
    }

    protected static function trade_no_to_order_no($trade_no, $order_prefix = '')
    {
        return (int) substr($trade_no, strlen($order_prefix), strrpos($trade_no, 'TS'));
    }

    protected static function get_item_name($item_name, $order)
    {
        if (empty($item_name)) {
            $items = $order->get_items();
            if (count($items)) {
                $item = reset($items);
                $item_name = trim($item->get_name());
            }
        }
        $item_name = str_replace(['^','\'','`','!','@','＠','#','%','&','*','+','\\','"','<','>','|','_','[',']'], '', $item_name);

        return $item_name;
    }

    public static function gateway_return()
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
        die();
    }

    protected static function submit_sctipt($action_script, $order)
    {
        $submit_ID = time();
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
            },
            onBlock: function(){ ' . $action_script . ' }
        });';
        echo "\n" . '<script type="text/javascript">window.addEventListener("pageshow", function(e) {
            let submitID = sessionStorage.getItem("RYWT_submitID");
            if(submitID == "' . esc_attr($submit_ID) . '") {
                location.href = ' . json_encode($order->get_checkout_order_received_url()) . '
            } else {
                sessionStorage.setItem("RYWT_submitID", "' . esc_attr($submit_ID) . '");
                jQuery(function($){ ' . $blockUI . ' });
            }
        });</script>' . "\n";
    }

    public static function set_do_die()
    {
        self::$do_die = true;
    }

    public static function set_not_do_die()
    {
        self::$do_die = false;
    }

    protected static function die_success()
    {
        if (self::$do_die) {
            die('1|OK');
        }
    }

    protected static function die_error()
    {
        if (self::$do_die) {
            die('0|');
        }
    }
}
