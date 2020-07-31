<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_SmilePay_Shipping_admin
{
    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/smilepay-shipping-meta-box.php';

        add_filter('woocommerce_admin_shipping_fields', [__CLASS__, 'set_cvs_shipping_fields'], 99);
        add_action('woocommerce_shipping_zone_method_status_toggled', [__CLASS__, 'check_can_enable'], 10, 4);
        add_action('woocommerce_update_options_shipping_options', [__CLASS__, 'check_ship_destination']);
        add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
        add_action('woocommerce_order_action_get_new_smilepay_no', ['RY_SmilePay_Shipping_Api', 'get_csv_no']);
        add_action('woocommerce_order_action_get_new_smilepay_no_cod', ['RY_SmilePay_Shipping_Api', 'get_csv_no_cod']);
        add_action('woocommerce_order_action_send_at_cvs_email', ['RY_SmilePay_Shipping', 'send_at_cvs_email']);

        add_action('add_meta_boxes', ['RY_SmilePay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);
        add_action('wp_ajax_RY_SmilePay_Shipping_print', [__CLASS__, 'print_info']);
    }

    public static function set_cvs_shipping_fields($shipping_fields)
    {
        global $theorder;

        $shipping_method = false;
        if (!empty($theorder)) {
            $items_shipping = $theorder->get_items('shipping');
            $items_shipping = array_shift($items_shipping);
            if ($items_shipping) {
                $shipping_method = RY_SmilePay_Shipping::get_order_support_shipping($items_shipping);
            }
            if ($shipping_method !== false) {
                unset($shipping_fields['last_name']);
                unset($shipping_fields['company']);
                unset($shipping_fields['address_1']);
                unset($shipping_fields['address_2']);
                unset($shipping_fields['city']);
                unset($shipping_fields['postcode']);
                unset($shipping_fields['country']);
                unset($shipping_fields['state']);

                $shipping_fields['cvs_store_ID'] = [
                    'label' => __('Store ID', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['cvs_store_name'] = [
                    'label' => __('Store Name', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['cvs_store_address'] = [
                    'label' => __('Store Address', 'ry-woocommerce-tools'),
                    'show' => false
                ];
                $shipping_fields['phone'] = [
                    'label' => __('Phone', 'woocommerce')
                ];
            }
        }
        return $shipping_fields;
    }

    public static function check_can_enable($instance_id, $method_id, $zone_id, $is_enabled)
    {
        if (array_key_exists($method_id, RY_SmilePay_Shipping::$support_methods)) {
            if ($is_enabled == 1) {
                if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                    global $wpdb;

                    $wpdb->update(
                        $wpdb->prefix . 'woocommerce_shipping_zone_methods',
                        [
                            'is_enabled' => 0
                        ],
                        [
                            'instance_id' => absint($instance_id)
                        ]
                    );
                }
            }
        }
    }

    public static function check_ship_destination()
    {
        global $wpdb;
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            foreach (['ry_smilepay_shipping_cvs'] as $method_id) {
                $wpdb->update(
                    $wpdb->prefix . 'woocommerce_shipping_zone_methods',
                    [
                        'is_enabled' => 0
                    ],
                    [
                        'method_id' => $method_id,
                    ]
                );
            }

            WC_Admin_Settings::add_error(__('All cvs shipping methods set to disable.', 'ry-woocommerce-tools'));
        }
    }

    public static function add_order_actions($order_actions)
    {
        global $theorder;
        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item_id => $item) {
            if (RY_SmilePay_Shipping::get_order_support_shipping($item) !== false) {
                $order_actions['get_new_smilepay_no'] = __('Get new SmilePay shipping no', 'ry-woocommerce-tools');
                if ($theorder->get_payment_method() == 'cod') {
                    $order_actions['get_new_smilepay_no_cod'] = __('Get new SmilePay shipping no with cod', 'ry-woocommerce-tools');
                }
                if ($theorder->has_status(['ry-at-cvs'])) {
                    $order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
                }
            }
        }
        return $order_actions;
    }

    public static function print_info()
    {
        $order_ID = (int) $_GET['orderid'];
        $logistics_id = (int) $_GET['id'];

        $print_info = '';
        $order = wc_get_order($order_ID);
        if (!$order) {
            wp_redirect(admin_url('edit.php?post_type=shop_order'));
            exit();
        }

        foreach ($order->get_items('shipping') as $item_id => $item) {
            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                continue;
            }
            foreach ($shipping_list as $info) {
                if ($info['ID'] != $logistics_id) {
                    continue;
                }

                wp_redirect(RY_SmilePay_Shipping_Api::get_print_url($info));
                exit();
            }
        }

        wp_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
        exit();
    }
}

RY_SmilePay_Shipping_admin::init();
