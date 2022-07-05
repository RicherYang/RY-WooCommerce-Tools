<?php
final class RY_ECPay_Shipping_admin
{
    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/ecpay-shipping-meta-box.php';

        add_action('admin_menu', [__CLASS__, 'admin_menu'], 15);

        add_filter('woocommerce_admin_shipping_fields', [__CLASS__, 'set_cvs_shipping_fields'], 99);
        add_action('woocommerce_shipping_zone_method_status_toggled', [__CLASS__, 'check_can_enable'], 10, 4);
        add_action('woocommerce_update_options_shipping_options', [__CLASS__, 'check_ship_destination']);
        add_action('add_meta_boxes', ['RY_ECPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        if ('yes' === RY_WT::get_option('ecpay_shipping', 'no')) {
            add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
            add_action('woocommerce_order_action_get_new_ecpay_no', ['RY_ECPay_Shipping_Api', 'get_code']);
            add_action('woocommerce_order_action_get_new_ecpay_no_cod', ['RY_ECPay_Shipping_Api', 'get_code_cod']);
            add_action('woocommerce_order_action_send_at_cvs_email', ['RY_ECPay_Shipping', 'send_at_cvs_email']);
        }
    }

    public static function admin_menu()
    {
        add_submenu_page(null, 'RY ECPay shipping print', null, 'edit_shop_orders', 'ry_print_ecpay_shipping', [__CLASS__, 'print_shipping']);
    }

    public static function set_cvs_shipping_fields($shipping_fields)
    {
        global $theorder;

        $shipping_method = false;
        if (!empty($theorder)) {
            $items_shipping = $theorder->get_items('shipping');
            $items_shipping = array_shift($items_shipping);
            if ($items_shipping) {
                $shipping_method = RY_ECPay_Shipping::get_order_support_shipping($items_shipping);
            }
            if ($shipping_method !== false) {
                if (strpos($shipping_method, 'cvs') !== false) {
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
                    $shipping_fields['cvs_store_telephone'] = [
                        'label' => __('Store Telephone', 'ry-woocommerce-tools'),
                        'show' => false
                    ];
                }

                $shipping_fields['phone'] = [
                    'label' => __('Phone', 'ry-woocommerce-tools')
                ];
            } elseif ('yes' == RY_WT::get_option('keep_shipping_phone', 'no')) {
                $shipping_fields['phone'] = [
                    'label' => __('Phone', 'ry-woocommerce-tools')
                ];
            }
        }
        return $shipping_fields;
    }

    public static function check_can_enable($instance_id, $method_id, $zone_id, $is_enabled)
    {
        if ($is_enabled != 1) {
            return;
        }
        if (array_key_exists($method_id, RY_ECPay_Shipping::$support_methods)) {
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

    public static function check_ship_destination()
    {
        global $wpdb;
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            RY_WT::update_option('ecpay_shipping_cvs_type', 'disable');
            foreach (['ry_ecpay_shipping_cvs_711', 'ry_ecpay_shipping_cvs_hilife', 'ry_ecpay_shipping_cvs_family'] as $method_id) {
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
        } else {
            if (RY_WT::get_option('ecpay_shipping_cvs_type') == 'disable') {
                RY_WT::update_option('ecpay_shipping_cvs_type', 'C2C');
            }
        }
    }

    public static function add_order_actions($order_actions)
    {
        global $theorder, $post;
        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item) {
            if (RY_ECPay_Shipping::get_order_support_shipping($item) !== false) {
                $order_actions['get_new_ecpay_no'] = __('Get new Ecpay shipping no', 'ry-woocommerce-tools');
                if ($theorder->get_payment_method() == 'cod') {
                    $order_actions['get_new_ecpay_no_cod'] = __('Get new Ecpay shipping no (cod)', 'ry-woocommerce-tools');
                }
                if ($theorder->has_status(['ry-at-cvs'])) {
                    $order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
                }
            }
        }
        return $order_actions;
    }

    public static function print_shipping()
    {
        $order_ID = wp_unslash($_GET['orderid'] ?? '');
        $logistics_ID = (int) wp_unslash($_GET['id'] ?? '');
        $print_list = [];

        if ($logistics_ID > 0) {
            $order = wc_get_order((int) $order_ID);
            if (empty($order)) {
                wp_redirect(admin_url('edit.php?post_type=shop_order'));
                exit();
            }

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (is_array($shipping_list)) {
                foreach ($shipping_list as $info) {
                    if ($info['ID'] == $logistics_ID) {
                        $print_list[] = $info;
                    }
                }
            }
        } else {
            $print_type = wp_unslash($_GET['type']);
            $order_IDs = explode(',', $order_ID);
            foreach ($order_IDs as $order_ID) {
                $order = wc_get_order((int) $order_ID);
                if (empty($order)) {
                    continue;
                }
                $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                if (is_array($shipping_list)) {
                    foreach ($shipping_list as $info) {
                        switch ($info['LogisticsSubType']) {
                            case 'UNIMART':
                            case 'UNIMARTC2C':
                                if ($print_type == 'cvs_711') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'FAMI':
                            case 'FAMIC2C':
                                if ($print_type == 'cvs_family') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'HILIFE':
                            case 'HILIFEC2C':
                                if ($print_type == 'cvs_hilife') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'OKMARTC2C':
                                if ($print_type == 'cvs_ok') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'TCAT':
                                if ($print_type == 'home_tcat') {
                                    $print_list[] = $info;
                                }
                                break;
                        }
                    }
                }
            }
        }

        if (!empty($print_list)) {
            RY_ECPay_Shipping_Api::get_print_form($print_list);
        }
        exit();
    }
}

RY_ECPay_Shipping_admin::init();
