<?php

final class RY_WT_Cron
{
    public static function add_action(): void
    {
        add_action('ry_check_ntp_time', [__CLASS__, 'check_ntp_time']);

        add_action('ry_wt_update_3_2_0', [__CLASS__, 'update_3_2_0']);
    }

    public static function check_ntp_time()
    {
        if (!function_exists('stream_socket_client')) {
            wp_unschedule_hook('ry_check_ntp_time');
            return;
        }

        $ntp_time = 0;
        $ntp_server_url = apply_filters('ry_ntp_server_url', 'udp://time.google.com:123');
        $socket = stream_socket_client($ntp_server_url, $errno, $errstr);
        if ($socket) {
            fwrite($socket, chr(0x1B) . str_repeat(chr(0x00), 47)); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
            $response = fread($socket, 48); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
            fclose($socket); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            if (!empty($response)) {
                $data = @unpack('N12', $response);
                if (is_array($data) && isset($data[9])) {
                    $ntp_time = sprintf('%u', $data[9]) - 2208988800;
                }
            }
        }

        if ($ntp_time > 0) {
            $time_diff = current_time('timestamp', true) - $ntp_time;
            RY_WT::update_option('ntp_time_error', abs($time_diff) > MINUTE_IN_SECONDS);
            return $time_diff;
        }
    }

    public static function update_3_2_0(): void
    {
        $start_time = microtime(true);

        $args = [
            'type' => 'shop_order',
            'return' => 'ids',
            'meta_query' => [[ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
            $query = new WC_Order_Query($args);
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
