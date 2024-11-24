<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

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

    protected function submit_sctipt($action_script)
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

        wc_enqueue_js($blockUI . ' setTimeout(function() { ' . $action_script . ' }, 150);');
    }

    public function set_do_die()
    {
        $this->do_die = true;
    }

    public function set_not_do_die()
    {
        $this->do_die = false;
    }

    protected function get_shipping_package($order, $method_class, $declare_over_type, $for_temp, $default_weight)
    {
        $package_list = [];
        $temp_package = [];
        $basic_package = [
            'price' => 0,
            'fee' => 0,
            'weight' => 0,
            'size' => 0,
            'items' => 0,
        ];

        foreach ($order->get_items('line_item') as $item) {
            $product = $item->get_product();
            if ($product) {
                $temp = $product->get_meta('_ry_shipping_temp', true);
                if (empty($temp) && 'variation' === $product->get_type()) {
                    $parent_product = wc_get_product($product->get_parent_id());
                    $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                }
                $temp = in_array($temp, $method_class::get_support_temp()) ? $temp : '1';
                $weight = $product->get_weight();
                $size = (float) $product->get_length() + (float) $product->get_width() + (float) $product->get_height();

                $shipping_amount = $product->get_meta('_ry_shipping_amount', true);
                if ('' == $shipping_amount) {
                    if ('variation' === $product->get_type()) {
                        $parent_product = wc_get_product($product->get_parent_id());
                        $shipping_amount = $parent_product->get_meta('_ry_shipping_amount', true);
                    }
                }
                $shipping_amount = NumberUtil::round($shipping_amount, wc_get_price_decimals());
                if (0 >= $shipping_amount) {
                    $shipping_amount = $product->get_regular_price();
                }
                $shipping_amount = NumberUtil::round($shipping_amount, wc_get_price_decimals());
                $item_price = $shipping_amount * $item->get_quantity();
            } else {
                $temp = 1;
                $weight = '';
                $size = 0;
                $item_price = $item->get_subtotal();
            }

            if (null !== $for_temp && $temp != $for_temp) {
                continue;
            }

            if (!isset($temp_package[$temp])) {
                $package_list[] = $basic_package;
                $temp_package[$temp] = array_key_last($package_list);
                $package_list[$temp_package[$temp]]['temp'] = $temp;
            }

            if ('' == $weight) {
                $weight = $default_weight;
            }
            $weight = (float) $weight;

            if ('multi' === $declare_over_type) {
                if (20000 < $item_price) {
                    array_unshift($package_list, $basic_package);
                    $package_list[0]['temp'] = $temp;
                    $package_list[0]['items'] += 1;
                    $package_list[0]['price'] += $item_price;
                    $package_list[0]['fee'] += $item->get_total();
                    $package_list[0]['weight'] += $weight * $item->get_quantity();
                    $package_list[0]['size'] = $size;

                    $temp_package[$temp] += 1;
                    continue;
                }

                if (20000 < $package_list[$temp_package[$temp]]['price'] + $item_price) {
                    $package_list[] = $basic_package;
                    $temp_package[$temp] = array_key_last($package_list);
                    $package_list[$temp_package[$temp]]['temp'] = $temp;
                }
            }

            $package_list[$temp_package[$temp]]['items'] += 1;
            $package_list[$temp_package[$temp]]['price'] += $item_price;
            $package_list[$temp_package[$temp]]['fee'] += $item->get_total();
            $package_list[$temp_package[$temp]]['weight'] += $weight * $item->get_quantity();
            $package_list[$temp_package[$temp]]['size'] = max($size, $package_list[$temp_package[$temp]]['size']);
        }

        foreach ($package_list as $idx => $package_info) {
            if (0 === $package_info['items']) {
                unset($package_list[$idx]);
                continue;
            }

            $package_info['price'] = (int) $package_info['price'];
            $package_info['fee'] = (int) $package_info['fee'];
        }

        usort($package_list, function ($a, $b) {
            return $a['temp'] <=> $b['temp'];
        });

        return $package_list;
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
