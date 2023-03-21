<?php

final class RY_SmilePay_Shipping_admin
{
    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/smilepay-shipping-meta-box.php';

        add_action('add_meta_boxes', ['RY_SmilePay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        if ('yes' === RY_WT::get_option('smilepay_shipping', 'no')) {
            add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
            add_action('woocommerce_order_action_get_new_smilepay_no', ['RY_SmilePay_Shipping_Api', 'get_csv_no']);
            add_action('woocommerce_order_action_get_new_smilepay_no_cod', ['RY_SmilePay_Shipping_Api', 'get_csv_no_cod']);
            add_action('woocommerce_order_action_send_at_cvs_email', ['RY_SmilePay_Shipping', 'send_at_cvs_email']);

            add_action('wp_ajax_RY_SmilePay_Shipping_get_no', [__CLASS__, 'get_code_no']);
            add_action('wp_ajax_RY_SmilePay_Shipping_print', [__CLASS__, 'print_info']);
        }
    }

    public static function add_order_actions($order_actions)
    {
        global $theorder, $post;
        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item) {
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

    public static function get_code_no()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        ;
        $logistics_id = wp_unslash($_GET['id']);

        $print_info = '';
        $order = wc_get_order($order_ID);
        if (!$order) {
            wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
            exit();
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                continue;
            }
            if (isset($shipping_list[$logistics_id])) {
                if (empty($shipping_list[$logistics_id]['PaymentNo'])) {
                    RY_SmilePay_Shipping_Api::get_code_no($order_ID, $logistics_id);
                }
            }
        }

        wp_safe_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
        exit();
    }

    public static function print_info()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $logistics_id = wp_unslash($_GET['id']);

        $print_info = '';
        $order = wc_get_order($order_ID);
        if (!$order) {
            wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
            exit();
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                continue;
            }
            if (isset($shipping_list[$logistics_id])) {
                if (empty($shipping_list[$logistics_id]['PaymentNo'])) {
                    RY_SmilePay_Shipping_Api::get_code_no($order_ID, $logistics_id);

                    $order = wc_get_order($order_ID);
                    $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                }

                wp_redirect(RY_SmilePay_Shipping_Api::get_print_url($shipping_list[$logistics_id]));
                exit();
            }
        }

        wp_safe_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
        exit();
    }
}

RY_SmilePay_Shipping_admin::init();
