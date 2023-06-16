<?php

final class RY_WT_update
{
    public static function update()
    {
        global $wpdb;

        $now_version = RY_WT::get_option('version', '');

        if ($now_version == RY_WT_VERSION) {
            return;
        }

        if (!wp_next_scheduled('ry_check_ntp_time')) {
            wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
        }

        if (version_compare($now_version, '1.1.2', '<')) {
            @set_time_limit(300);

            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping.php';

            $wpdb->update($wpdb->postmeta, ['meta_key' => '_ecpay_shipping_info'], ['meta_key' => '_shipping_cvs_info']);

            $cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');
            $meta_rows = $wpdb->get_results(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ecpay_shipping_info'"
            );
            foreach ($meta_rows as $meta_row) {
                if ($order = wc_get_order($meta_row->post_id)) {
                    $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                    if (!is_array($shipping_list)) {
                        continue;
                    }

                    foreach ($order->get_items('shipping') as $item) {
                        $shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
                        if ($shipping_method !== false) {
                            $method_class = RY_ECPay_Shipping::$support_methods[$shipping_method];
                            foreach ($shipping_list as &$info) {
                                $info['LogisticsType'] = $method_class::$LogisticsType;
                                $info['LogisticsSubType'] = $method_class::$LogisticsSubType . (('C2C' == $cvs_type) ? 'C2C' : '');
                            }
                        }
                    }
                    $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                    $order->save_meta_data();
                }
            }

            RY_WT::update_option('version', '1.1.2');
        }

        if (version_compare($now_version, '1.4.0', '<')) {
            RY_WT::update_option('ecpay_shipping', RY_WT::get_option('ecpay_shipping_cvs', 'no'));

            RY_WT::update_option('version', '1.4.0');
        }

        if (version_compare($now_version, '1.6.0', '<')) {
            RY_WT::update_option('keep_shipping_phone', RY_WT::get_option('ecpay_keep_shipping_phone', 'no'));

            RY_WT::update_option('version', '1.6.0');
        }

        if (version_compare($now_version, '1.8.11', '<')) {
            RY_WT::update_option('ecpay_shipping_auto_order_status', RY_WT::get_option('ecpay_shipping_auto_completed', 'yes'));

            RY_WT::update_option('version', '1.8.11');
        }

        if (version_compare($now_version, '1.10.0', '<')) {
            RY_WT::update_option('smilepay_shipping_auto_order_status', RY_WT::get_option('smilepay_shipping_auto_completed', 'yes'));

            RY_WT::update_option('version', '1.10.0');
        }

        if (version_compare($now_version, '1.10.3', '<')) {
            RY_WT::update_option('ecpay_shipping_pickup_time', 4);
            RY_WT::update_option('ecpay_shipping_box_size', 1);

            RY_WT::update_option('version', '1.10.3');
        }

        if (version_compare($now_version, '2.0.1.1', '<')) {
            RY_WT::update_option('version', '2.0.1.1');
        }
    }
}
