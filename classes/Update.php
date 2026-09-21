<?php

namespace RY\WooCommerce;

defined('ABSPATH') or exit;

use RY\General\V20260810\Logs;
use RY\WooCommerce\Main;

final class Update
{
    public static function update()
    {
        global $wpdb;

        $now_version = Main::get_option('version', '0.0.0');

        if (RY_WT_VERSION === $now_version) {
            return;
        }

        if ($now_version === '0.0.0') {
            Main::update_option('version', RY_WT_VERSION, true);
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

                $cvs_type = Main::get_option('ecpay_shipping_cvs_type');

                $meta_rows = $wpdb->get_results("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ecpay_shipping_info'");
                foreach ($meta_rows as $meta_row) {
                    if ($order = wc_get_order($meta_row->post_id)) {
                        $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                        if (!is_array($shipping_list)) {
                            continue;
                        }

                        foreach ($order->get_items('shipping') as $shipping_item) {
                            $shipping_method = \RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($shipping_item);
                            if ($shipping_method) {
                                $method_class = \RY_WT_WC_ECPay_Shipping::$support_methods[$shipping_method];
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

            Main::update_option('version', '1.1.2', true);
        }

        if (version_compare($now_version, '1.4.0', '<')) {
            Main::update_option('ecpay_shipping', Main::get_option('ecpay_shipping_cvs', 'no'));

            Main::update_option('version', '1.4.0', true);
        }

        if (version_compare($now_version, '1.8.11', '<')) {
            Main::update_option('ecpay_shipping_auto_order_status', Main::get_option('ecpay_shipping_auto_completed', 'yes'));

            Main::update_option('version', '1.8.11', true);
        }

        if (version_compare($now_version, '1.10.0', '<')) {
            Main::update_option('smilepay_shipping_auto_order_status', Main::get_option('smilepay_shipping_auto_completed', 'yes'));

            Main::update_option('version', '1.10.0', true);
        }

        if (version_compare($now_version, '1.10.3', '<')) {
            Main::update_option('ecpay_shipping_box_size', 1);

            Main::update_option('version', '1.10.3', true);
        }

        if (version_compare($now_version, '3.0.0', '<')) {
            Main::delete_option('ecpay_gateway');
            Main::delete_option('ecpay_shipping');
            Main::delete_option('newebpay_gateway');
            Main::delete_option('newebpay_shipping');
            Main::delete_option('smilepay_gateway');
            Main::delete_option('smilepay_shipping');
            Main::delete_option('smilepay_shipping');
            Main::delete_option('ecpay_keep_shipping_phone');
            Main::delete_option('keep_shipping_phone');

            Main::update_option('version', '3.0.0', true);
        }

        if (version_compare($now_version, '3.2.1', '<')) {
            add_action('init', function () {
                WC()->queue()->schedule_single(time() + 10, 'ry_wt_update_3_2_0');

                Main::update_option('version', '3.2.1', true);
            });
        }

        if (version_compare($now_version, '3.4.20', '<')) {
            Main::delete_option('remove_site_visibility');

            Main::update_option('version', '3.4.20', true);
        }

        if (version_compare($now_version, '3.5.10', '<')) {
            Main::update_option('ecpay_shipping_declare_mode', 'payment');

            Main::update_option('version', '3.5.10', true);
        }

        if (version_compare($now_version, '3.6.1', '<')) {
            Main::update_option('version', '3.6.1', true);
        }

        if (version_compare($now_version, '3.6.2', '<')) {
            Main::update_option('shipping_product_weight', Main::get_option('ecpay_shipping_product_weight'));
            Main::delete_option('ecpay_shipping_product_weight');

            add_action('init', function () {
                $shipping_zones = \WC_Shipping_Zones::get_zones();
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
            Main::update_option('version', '3.6.2', true);
        }

        if (version_compare($now_version, '3.8.0', '<')) {
            if (Main::get_option('ecpay_gateway_MerchantID') !== false) {
                Main::update_option('ecpay_gateway_apiinfo', [
                    'prefix' => Main::get_option('ecpay_gateway_order_prefix'),
                    'itemname' => Main::get_option('payment_item_name'),
                    'testmode' => Main::get_option('ecpay_gateway_testmode'),
                    'MerchantID' => Main::get_option('ecpay_gateway_MerchantID'),
                    'HashKey' => Main::get_option('ecpay_gateway_HashKey'),
                    'HashIV' => Main::get_option('ecpay_gateway_HashIV'),
                ], false);
                Main::delete_option('ecpay_gateway_order_prefix');
                Main::delete_option('ecpay_gateway_testmode');
                Main::delete_option('ecpay_gateway_MerchantID');
                Main::delete_option('ecpay_gateway_HashKey');
                Main::delete_option('ecpay_gateway_HashIV');
            }

            if (Main::get_option('newebpay_gateway_MerchantID') !== false) {
                Main::update_option('newebpay_gateway_apiinfo', [
                    'prefix' => Main::get_option('newebpay_gateway_order_prefix'),
                    'itemname' => Main::get_option('payment_item_name'),
                    'testmode' => Main::get_option('newebpay_gateway_testmode'),
                    'MerchantID' => Main::get_option('newebpay_gateway_MerchantID'),
                    'HashKey' => Main::get_option('newebpay_gateway_HashKey'),
                    'HashIV' => Main::get_option('newebpay_gateway_HashIV'),
                ], false);
                Main::delete_option('newebpay_gateway_order_prefix');
                Main::delete_option('newebpay_gateway_testmode');
                Main::delete_option('newebpay_gateway_MerchantID');
                Main::delete_option('newebpay_gateway_HashKey');
                Main::delete_option('newebpay_gateway_HashIV');
            }

            if (Main::get_option('payuni_gateway_MerID') !== false) {
                Main::update_option('payuni_gateway_apiinfo', [
                    'prefix' => Main::get_option('payuni_gateway_order_prefix'),
                    'itemname' => Main::get_option('payment_item_name'),
                    'testmode' => Main::get_option('payuni_gateway_testmode'),
                    'MerID' => Main::get_option('payuni_gateway_MerID'),
                    'HashKey' => Main::get_option('payuni_gateway_HashKey'),
                    'HashIV' => Main::get_option('payuni_gateway_HashIV'),
                ], false);
                Main::delete_option('payuni_gateway_order_prefix');
                Main::delete_option('payuni_gateway_testmode');
                Main::delete_option('payuni_gateway_MerID');
                Main::delete_option('payuni_gateway_HashKey');
                Main::delete_option('payuni_gateway_HashIV');
            }

            if (Main::get_option('smilepay_gateway_MerID') !== false) {
                Main::update_option('smilepay_gateway_apiinfo', [
                    'prefix' => Main::get_option('smilepay_gateway_order_prefix'),
                    'itemname' => Main::get_option('payment_item_name'),
                    'testmode' => Main::get_option('smilepay_gateway_testmode'),
                    'Dcvc' => Main::get_option('smilepay_gateway_Dcvc'),
                    'Rvg2c' => Main::get_option('smilepay_gateway_Rvg2c'),
                    'Verify_key' => Main::get_option('smilepay_gateway_Verify_key'),
                    'Rot_check' => Main::get_option('smilepay_gateway_Rot_check'),
                ], false);
                Main::delete_option('smilepay_gateway_order_prefix');
                Main::delete_option('smilepay_gateway_testmode');
                Main::delete_option('smilepay_gateway_Dcvc');
                Main::delete_option('smilepay_gateway_Rvg2c');
                Main::delete_option('smilepay_gateway_Verify_key');
                Main::delete_option('smilepay_gateway_Rot_check');
            }

            if (Main::get_option('ecpay_shipping_MerchantID') !== false) {
                Main::update_option('ecpay_shipping_apiinfo', [
                    'prefix' => Main::get_option('ecpay_shipping_order_prefix'),
                    'cleanup_name' => Main::get_option('ecpay_shipping_cleanup_receiver_name'),
                    'itemname' => Main::get_option('shipping_item_name'),
                    'name' => Main::get_option('ecpay_shipping_sender_name'),
                    'phone' => Main::get_option('ecpay_shipping_sender_phone'),
                    'cellphone' => Main::get_option('ecpay_shipping_sender_cellphone'),
                    'zipcode' => Main::get_option('ecpay_shipping_sender_zipcode'),
                    'address' => Main::get_option('ecpay_shipping_sender_address'),
                    'declare_mode' => Main::get_option('ecpay_shipping_declare_mode'),
                    'declare_over' => Main::get_option('ecpay_shipping_declare_over'),
                    'print' => '1',
                    'testmode' => Main::get_option('ecpay_shipping_testmode'),
                    'MerchantID' => Main::get_option('ecpay_shipping_MerchantID'),
                    'HashKey' => Main::get_option('ecpay_shipping_HashKey'),
                    'HashIV' => Main::get_option('ecpay_shipping_HashIV'),
                ], false);
                Main::delete_option('ecpay_shipping_order_prefix');
                Main::delete_option('ecpay_shipping_cleanup_receiver_name');
                Main::delete_option('ecpay_shipping_sender_name');
                Main::delete_option('ecpay_shipping_sender_phone');
                Main::delete_option('ecpay_shipping_sender_cellphone');
                Main::delete_option('ecpay_shipping_sender_zipcode');
                Main::delete_option('ecpay_shipping_sender_address');
                Main::delete_option('ecpay_shipping_declare_mode');
                Main::delete_option('ecpay_shipping_declare_over');
                Main::delete_option('ecpay_shipping_testmode');
                Main::delete_option('ecpay_shipping_MerchantID');
                Main::delete_option('ecpay_shipping_HashKey');
                Main::delete_option('ecpay_shipping_HashIV');
            }

            if (Main::get_option('shipping_item_name') !== false) {
                Main::update_option('smilepay_shipping_apiinfo', [
                    'itemname' => Main::get_option('shipping_item_name'),
                    'print' => Main::get_option('smilepay_shipping_tcat_print_format'),
                    'delivery_date' => Main::get_option('smilepay_shipping_tcat_delivery_date'),
                ], false);
                Main::delete_option('smilepay_shipping_tcat_print_format');
                Main::delete_option('smilepay_shipping_tcat_delivery_date');
            }

            Main::delete_option('payment_item_name');
            Main::delete_option('shipping_item_name');

            Main::update_option('shipping_apiinfo', [
                'weight' => Main::get_option('shipping_product_weight'),
            ], false);
            Main::delete_option('shipping_product_weight');

            Main::update_option('version', '3.8.0', true);
        }

        if (version_compare($now_version, '2026.7.27', '<')) {
            $old_dir = WP_CONTENT_DIR . '/ry-logs';
            if (is_dir($old_dir)) {
                $new_dir = Logs::get_log_directory();
                foreach (new \FilesystemIterator($old_dir, \FilesystemIterator::SKIP_DOTS) as $file) {
                    @rename($file->getPathname(), $new_dir . $file->getFilename());
                }
                @rmdir($old_dir);
            }

            Main::update_option('version', '2026.7.27', true);
        }

        if (version_compare($now_version, '2026.7.31', '<')) {
            add_action('init', function () {
                as_unschedule_all_actions('RY_log_action');
            });

            Main::update_option('version', '2026.7.31', true);
        }

        if (version_compare($now_version, '2026.8.5', '<')) {
            add_action('init', function () {
                if (class_exists('\RY\General\V20260801\Logs')) {
                    $file_dir = \RY\General\V20260801\Logs::get_log_directory();
                    foreach (new \FilesystemIterator($file_dir, \FilesystemIterator::SKIP_DOTS) as $file) {
                        if ($file->isFile() && $file->isReadable()) {
                            if ($file->getExtension() === 'log') {
                                $file_name = $file->getBasename('.log');
                                $parts = explode('-', $file_name);
                                if (count($parts) > 4) {
                                    $hash_suffix = array_pop($parts);
                                    $date_suffix = implode('-', array_slice($parts, -3));
                                    $handle = implode('-', array_slice($parts, 0, -3));
                                    if (wp_hash($handle) === $hash_suffix) {
                                        $file_name = sanitize_file_name(implode('-', [$handle, $date_suffix, wp_hash($handle . $date_suffix)]) . '.log');
                                        rename($file->getPathname(), $file_dir . '/' . $file_name);
                                    }
                                }
                            }
                        }
                    }

                    Main::update_option('version', '2026.8.5', true);
                }
            });
        }

        if (version_compare($now_version, '2026.9.16', '<')) {
            Main::update_option('version', '2026.9.16', true);
        }
    }
}
