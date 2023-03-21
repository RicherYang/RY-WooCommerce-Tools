<?php

final class RY_ECPay_Shipping_admin
{
    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/ecpay-shipping-meta-box.php';

        add_action('add_meta_boxes', ['RY_ECPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        if ('yes' === RY_WT::get_option('ecpay_shipping', 'no')) {
            add_action('admin_post_ry-print-ecpay-shipping', [__CLASS__, 'print_shipping']);
            add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
            add_action('woocommerce_order_action_get_new_ecpay_no', ['RY_ECPay_Shipping_Api', 'get_code']);
            add_action('woocommerce_order_action_get_new_ecpay_no_cod', ['RY_ECPay_Shipping_Api', 'get_code_cod']);
            add_action('woocommerce_order_action_send_at_cvs_email', ['RY_ECPay_Shipping', 'send_at_cvs_email']);
        }
    }

    public static function print_shipping()
    {
        $order_ID = wp_unslash($_GET['orderid'] ?? '');
        $logistics_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $print_list = [];

        if ($logistics_ID > 0) {
            $order = wc_get_order((int) $order_ID);
            if (!empty($order)) {
                $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                if (is_array($shipping_list)) {
                    foreach ($shipping_list as $info) {
                        if ($info['ID'] == $logistics_ID) {
                            $print_list[] = $info;
                        }
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
                            case 'POST':
                                if ($print_type == 'home_post') {
                                    $print_list[] = $info;
                                }
                                break;
                        }
                    }
                }
            }
        }

        if (empty($print_list)) {
            wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
        } else {
            RY_ECPay_Shipping_Api::get_print_form($print_list);
        }
        exit();
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
}

RY_ECPay_Shipping_admin::init();
