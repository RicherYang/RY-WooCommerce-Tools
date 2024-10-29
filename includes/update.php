<?php

final class RY_WT_Update
{
    public static function update()
    {
        global $wpdb;

        $now_version = RY_WT::get_option('version', '');

        if (RY_WT_VERSION === $now_version) {
            return;
        }

        if (!wp_next_scheduled('ry_check_ntp_time')) {
            wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
        }

        if (version_compare($now_version, '1.1.2', '<')) {
            @set_time_limit(300);

            if (!empty($now_version)) {
                include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping.php';

                $wpdb->update($wpdb->postmeta, ['meta_key' => '_ecpay_shipping_info'], ['meta_key' => '_shipping_cvs_info']);

                $cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');
                $meta_rows = $wpdb->get_results("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ecpay_shipping_info'");
                foreach ($meta_rows as $meta_row) {
                    if ($order = wc_get_order($meta_row->post_id)) {
                        $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                        if (!is_array($shipping_list)) {
                            continue;
                        }

                        foreach ($order->get_items('shipping') as $item) {
                            $shipping_method = RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($item);
                            if (false !== $shipping_method) {
                                $method_class = RY_WT_WC_ECPay_Shipping::$support_methods[$shipping_method];
                                foreach ($shipping_list as &$info) {
                                    $info['LogisticsType'] = $method_class::Shipping_Type;
                                    $info['LogisticsSubType'] = $method_class::Shipping_Sub_Type . (('C2C' === $cvs_type) ? 'C2C' : '');
                                }
                            }
                        }
                        $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                        $order->save();
                    }
                }
            }

            RY_WT::update_option('version', '1.1.2');
        }

        if (version_compare($now_version, '1.4.0', '<')) {
            RY_WT::update_option('ecpay_shipping', RY_WT::get_option('ecpay_shipping_cvs', 'no'));

            RY_WT::update_option('version', '1.4.0');
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

        if (version_compare($now_version, '3.0.0', '<')) {
            RY_WT::delete_option('ecpay_gateway');
            RY_WT::delete_option('ecpay_shipping');
            RY_WT::delete_option('newebpay_gateway');
            RY_WT::delete_option('newebpay_shipping');
            RY_WT::delete_option('smilepay_gateway');
            RY_WT::delete_option('smilepay_shipping');
            RY_WT::delete_option('smilepay_shipping');
            RY_WT::delete_option('ecpay_keep_shipping_phone');
            RY_WT::delete_option('keep_shipping_phone');

            RY_WT::update_option('version', '3.0.0');
        }

        if (version_compare($now_version, '3.2.1', '<')) {
            add_action('init', function () {
                WC()->queue()->schedule_single(time() + 10, 'ry_wt_update_3_2_0');

                RY_WT::update_option('version', '3.2.1');
            });
        }

        if (version_compare($now_version, '3.4.18', '<')) {
            RY_WT::update_option('version', '3.4.18', true);
        }
    }
}
