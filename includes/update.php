<?php

defined('ABSPATH') or exit;

final class RY_WT_Update
{
    public static function update()
    {
        global $wpdb;

        $now_version = RY_WT::get_option('version', '0.0.0');

        if (RY_WT_VERSION === $now_version) {
            return;
        }

        if ($now_version === '0.0.0') {
            RY_WT::update_option('version', RY_WT_VERSION, true);
            return;
        }

        if (!wp_next_scheduled('ry_check_ntp_time')) {
            wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
        }

        if (version_compare($now_version, '1.1.2', '<')) {
            @set_time_limit(300);

            if (!empty($now_version)) {
                include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping.php';

                $wpdb->update($wpdb->postmeta, [
                    'meta_key' => '_ecpay_shipping_info',
                ], [
                    'meta_key' => '_shipping_cvs_info',
                ]);

                $cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');

                $meta_rows = $wpdb->get_results("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ecpay_shipping_info'");
                foreach ($meta_rows as $meta_row) {
                    if ($order = wc_get_order($meta_row->post_id)) {
                        $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                        if (!is_array($shipping_list)) {
                            continue;
                        }

                        foreach ($order->get_items('shipping') as $shipping_item) {
                            $shipping_method = RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($shipping_item);
                            if ($shipping_method) {
                                $method_class = RY_WT_WC_ECPay_Shipping::$support_methods[$shipping_method];
                                foreach ($shipping_list as &$info) {
                                    $info['LogisticsType'] = $method_class::SHIPPING_TYPE;
                                    $info['LogisticsSubType'] = $method_class::Shipping_Sub_Type . (('C2C' === $cvs_type) ? 'C2C' : '');
                                }
                            }
                        }
                        $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                        $order->save();
                    }
                }
            }

            RY_WT::update_option('version', '1.1.2', true);
        }

        if (version_compare($now_version, '1.4.0', '<')) {
            RY_WT::update_option('ecpay_shipping', RY_WT::get_option('ecpay_shipping_cvs', 'no'));

            RY_WT::update_option('version', '1.4.0', true);
        }

        if (version_compare($now_version, '1.8.11', '<')) {
            RY_WT::update_option('ecpay_shipping_auto_order_status', RY_WT::get_option('ecpay_shipping_auto_completed', 'yes'));

            RY_WT::update_option('version', '1.8.11', true);
        }

        if (version_compare($now_version, '1.10.0', '<')) {
            RY_WT::update_option('smilepay_shipping_auto_order_status', RY_WT::get_option('smilepay_shipping_auto_completed', 'yes'));

            RY_WT::update_option('version', '1.10.0', true);
        }

        if (version_compare($now_version, '1.10.3', '<')) {
            RY_WT::update_option('ecpay_shipping_box_size', 1);

            RY_WT::update_option('version', '1.10.3', true);
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

            RY_WT::update_option('version', '3.0.0', true);
        }

        if (version_compare($now_version, '3.2.1', '<')) {
            add_action('init', function () {
                WC()->queue()->schedule_single(time() + 10, 'ry_wt_update_3_2_0');

                RY_WT::update_option('version', '3.2.1', true);
            });
        }

        if (version_compare($now_version, '3.4.20', '<')) {
            RY_WT::delete_option('remove_site_visibility');

            RY_WT::update_option('version', '3.4.20', true);
        }

        if (version_compare($now_version, '3.5.10', '<')) {
            RY_WT::update_option('ecpay_shipping_declare_mode', 'payment');

            RY_WT::update_option('version', '3.5.10', true);
        }

        if (version_compare($now_version, '3.6.1', '<')) {
            RY_WT::update_option('version', '3.6.1', true);
        }

        if (version_compare($now_version, '3.6.2', '<')) {
            RY_WT::update_option('shipping_product_weight', RY_WT::get_option('ecpay_shipping_product_weight'));
            RY_WT::delete_option('ecpay_shipping_product_weight');

            add_action('init', function () {
                $shipping_zones = WC_Shipping_Zones::get_zones();
                foreach ($shipping_zones as $zone) {
                    foreach ($zone['shipping_methods'] as $method) {
                        if (str_starts_with($method->id, 'ry_ecpay')) {
                            $method->init_instance_settings();

                            $settings = $method->instance_settings;
                            if (is_numeric($settings['cost'])) {
                                $shortcode = '';
                                if (isset($settings['cost_offisland']) && !empty($settings['cost_offisland'])) {
                                    $shortcode .= ' offisland="' . $settings['cost_offisland'] . '"';
                                    unset($settings['cost_offisland']);
                                }
                                if (isset($settings['cost_cool']) && !empty($settings['cost_cool'])) {
                                    $shortcode .= ' cool="' . $settings['cost_cool'] . '"';
                                    unset($settings['cost_cool']);
                                }
                                if ($shortcode !== '') {
                                    $settings['cost'] .= ' + [addfee' . $shortcode . ']';
                                    update_option($method->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $method->id . '_instance_settings_values', $settings, $method), 'yes');
                                }
                            }
                        }
                    }
                }
            });
            RY_WT::update_option('version', '3.6.2', true);
        }

        if (version_compare($now_version, '3.8.0', '<')) {
            if (RY_WT::get_option('ecpay_gateway_MerchantID') !== false) {
                RY_WT::update_option('ecpay_gateway_apiinfo', [
                    'prefix' => RY_WT::get_option('ecpay_gateway_order_prefix'),
                    'itemname' => RY_WT::get_option('payment_item_name'),
                    'testmode' => RY_WT::get_option('ecpay_gateway_testmode'),
                    'MerchantID' => RY_WT::get_option('ecpay_gateway_MerchantID'),
                    'HashKey' => RY_WT::get_option('ecpay_gateway_HashKey'),
                    'HashIV' => RY_WT::get_option('ecpay_gateway_HashIV'),
                ], false);
                RY_WT::delete_option('ecpay_gateway_order_prefix');
                RY_WT::delete_option('ecpay_gateway_testmode');
                RY_WT::delete_option('ecpay_gateway_MerchantID');
                RY_WT::delete_option('ecpay_gateway_HashKey');
                RY_WT::delete_option('ecpay_gateway_HashIV');
            }

            if (RY_WT::get_option('newebpay_gateway_MerchantID') !== false) {
                RY_WT::update_option('newebpay_gateway_apiinfo', [
                    'prefix' => RY_WT::get_option('newebpay_gateway_order_prefix'),
                    'itemname' => RY_WT::get_option('payment_item_name'),
                    'testmode' => RY_WT::get_option('newebpay_gateway_testmode'),
                    'MerchantID' => RY_WT::get_option('newebpay_gateway_MerchantID'),
                    'HashKey' => RY_WT::get_option('newebpay_gateway_HashKey'),
                    'HashIV' => RY_WT::get_option('newebpay_gateway_HashIV'),
                ], false);
                RY_WT::delete_option('newebpay_gateway_order_prefix');
                RY_WT::delete_option('newebpay_gateway_testmode');
                RY_WT::delete_option('newebpay_gateway_MerchantID');
                RY_WT::delete_option('newebpay_gateway_HashKey');
                RY_WT::delete_option('newebpay_gateway_HashIV');
            }

            if (RY_WT::get_option('payuni_gateway_MerID') !== false) {
                RY_WT::update_option('payuni_gateway_apiinfo', [
                    'prefix' => RY_WT::get_option('payuni_gateway_order_prefix'),
                    'itemname' => RY_WT::get_option('payment_item_name'),
                    'testmode' => RY_WT::get_option('payuni_gateway_testmode'),
                    'MerID' => RY_WT::get_option('payuni_gateway_MerID'),
                    'HashKey' => RY_WT::get_option('payuni_gateway_HashKey'),
                    'HashIV' => RY_WT::get_option('payuni_gateway_HashIV'),
                ], false);
                RY_WT::delete_option('payuni_gateway_order_prefix');
                RY_WT::delete_option('payuni_gateway_testmode');
                RY_WT::delete_option('payuni_gateway_MerID');
                RY_WT::delete_option('payuni_gateway_HashKey');
                RY_WT::delete_option('payuni_gateway_HashIV');
            }

            if (RY_WT::get_option('smilepay_gateway_MerID') !== false) {
                RY_WT::update_option('smilepay_gateway_apiinfo', [
                    'prefix' => RY_WT::get_option('smilepay_gateway_order_prefix'),
                    'itemname' => RY_WT::get_option('payment_item_name'),
                    'testmode' => RY_WT::get_option('smilepay_gateway_testmode'),
                    'Dcvc' => RY_WT::get_option('smilepay_gateway_Dcvc'),
                    'Rvg2c' => RY_WT::get_option('smilepay_gateway_Rvg2c'),
                    'Verify_key' => RY_WT::get_option('smilepay_gateway_Verify_key'),
                    'Rot_check' => RY_WT::get_option('smilepay_gateway_Rot_check'),
                ], false);
                RY_WT::delete_option('smilepay_gateway_order_prefix');
                RY_WT::delete_option('smilepay_gateway_testmode');
                RY_WT::delete_option('smilepay_gateway_Dcvc');
                RY_WT::delete_option('smilepay_gateway_Rvg2c');
                RY_WT::delete_option('smilepay_gateway_Verify_key');
                RY_WT::delete_option('smilepay_gateway_Rot_check');
            }

            if (RY_WT::get_option('ecpay_shipping_MerchantID') !== false) {
                RY_WT::update_option('ecpay_shipping_apiinfo', [
                    'prefix' => RY_WT::get_option('ecpay_shipping_order_prefix'),
                    'cleanup_name' => RY_WT::get_option('ecpay_shipping_cleanup_receiver_name'),
                    'itemname' => RY_WT::get_option('shipping_item_name'),
                    'name' => RY_WT::get_option('ecpay_shipping_sender_name'),
                    'phone' => RY_WT::get_option('ecpay_shipping_sender_phone'),
                    'cellphone' => RY_WT::get_option('ecpay_shipping_sender_cellphone'),
                    'zipcode' => RY_WT::get_option('ecpay_shipping_sender_zipcode'),
                    'address' => RY_WT::get_option('ecpay_shipping_sender_address'),
                    'declare_mode' => RY_WT::get_option('ecpay_shipping_declare_mode'),
                    'declare_over' => RY_WT::get_option('ecpay_shipping_declare_over'),
                    'print' => '1',
                    'testmode' => RY_WT::get_option('ecpay_shipping_testmode'),
                    'MerchantID' => RY_WT::get_option('ecpay_shipping_MerchantID'),
                    'HashKey' => RY_WT::get_option('ecpay_shipping_HashKey'),
                    'HashIV' => RY_WT::get_option('ecpay_shipping_HashIV'),
                ], false);
                RY_WT::delete_option('ecpay_shipping_order_prefix');
                RY_WT::delete_option('ecpay_shipping_cleanup_receiver_name');
                RY_WT::delete_option('ecpay_shipping_sender_name');
                RY_WT::delete_option('ecpay_shipping_sender_phone');
                RY_WT::delete_option('ecpay_shipping_sender_cellphone');
                RY_WT::delete_option('ecpay_shipping_sender_zipcode');
                RY_WT::delete_option('ecpay_shipping_sender_address');
                RY_WT::delete_option('ecpay_shipping_declare_mode');
                RY_WT::delete_option('ecpay_shipping_declare_over');
                RY_WT::delete_option('ecpay_shipping_testmode');
                RY_WT::delete_option('ecpay_shipping_MerchantID');
                RY_WT::delete_option('ecpay_shipping_HashKey');
                RY_WT::delete_option('ecpay_shipping_HashIV');
            }

            if (RY_WT::get_option('shipping_item_name') !== false) {
                RY_WT::update_option('smilepay_shipping_apiinfo', [
                    'itemname' => RY_WT::get_option('shipping_item_name'),
                    'print' => RY_WT::get_option('smilepay_shipping_tcat_print_format'),
                    'delivery_date' => RY_WT::get_option('smilepay_shipping_tcat_delivery_date'),
                ], false);
                RY_WT::delete_option('smilepay_shipping_tcat_print_format');
                RY_WT::delete_option('smilepay_shipping_tcat_delivery_date');
            }

            RY_WT::delete_option('payment_item_name');
            RY_WT::delete_option('shipping_item_name');

            RY_WT::update_option('shipping_apiinfo', [
                'weight' => RY_WT::get_option('shipping_product_weight'),
            ], false);
            RY_WT::delete_option('shipping_product_weight');

            RY_WT::update_option('version', '3.8.0', true);
        }

        if (version_compare($now_version, '2026.7.18', '<')) {
            RY_WT::update_option('version', '2026.7.18', true);
        }
    }
}
