<?php

namespace RY\WooCommerce;

use RY\Invoice\Amego\Main;

defined('ABSPATH') or exit;

final class Cron
{
    public static function add_action(): void
    {
        add_action('ry_wt_update_3_2_0', [__CLASS__, 'update_3_2_0']);
    }

    public static function update_3_2_0(): void
    {
        $start_time = microtime(true);

        $args = [
            'type' => 'shop_order',
            'return' => 'ids',
            'meta_query' => [[
                'key' => ['_ecpay_shipping_info', '_newebpay_shipping_info', '_smilepay_shipping_info'],
                'compare_key' => 'IN',
                'value' => 'LogisticsType',
                'compare' => 'NOT LIKE',
            ]],
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 100,
        ];
        while (true) {
            $query = new \WC_Order_Query($args);
            $order_IDs = $query->get_orders();
            if (empty($order_IDs)) {
                break;
            }

            foreach ($order_IDs as $order_ID) {
                $order = wc_get_order($order_ID);
                if ($order) {
                    foreach (['_ecpay_shipping_info', '_newebpay_shipping_info', '_smilepay_shipping_info'] as $meta_key) {
                        $shipping_list = $order->get_meta($meta_key, true);
                        if (is_array($shipping_list)) {
                            foreach ($shipping_list as $idx => $info) {
                                if (!isset($shipping_list[$idx]['LogisticsType'])) {
                                    $shipping_list[$idx]['LogisticsType'] = 'CVS';
                                }
                                if (!isset($shipping_list[$idx]['store_ID'])) {
                                    $shipping_list[$idx]['store_ID'] = $info['storeID'];
                                    unset($shipping_list[$idx]['storeID']);
                                }
                                if ($info['ID'] == $info['PaymentNo']) {
                                    $shipping_list[$idx]['PaymentNo'] = '';
                                }
                            }
                            $order->update_meta_data($meta_key, $shipping_list);
                            $order->save();
                        }
                    }
                }
            }

            if (10 < microtime(true) - $start_time) {
                WC()->queue()->schedule_single(time() + 10, 'ry_wt_update_3_2_0');
                return;
            }
        }
    }
}
