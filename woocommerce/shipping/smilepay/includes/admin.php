<?php

final class RY_WT_WC_SmilePay_Shipping_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_SmilePay_Shipping_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }
        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/smilepay-shipping-meta-box.php';

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_smilepay_shipping', [$this, 'check_option']);

        add_action('add_meta_boxes', ['RY_SmilePay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        add_filter('woocommerce_order_actions', [$this, 'add_order_actions']);
        add_action('woocommerce_order_action_get_new_smilepay_no', [RY_WT_WC_SmilePay_Shipping_Api::instance(), 'get_csv_no']);
        add_action('woocommerce_order_action_get_new_smilepay_no_cod', [RY_WT_WC_SmilePay_Shipping_Api::instance(), 'get_csv_no_cod']);
        add_action('woocommerce_order_action_send_at_cvs_email', [RY_WT_WC_SmilePay_Shipping::instance(), 'send_at_cvs_email']);

        add_action('wp_ajax_RY_SmilePay_Shipping_get_no', [$this, 'get_code_no']);
        add_action('wp_ajax_RY_SmilePay_Shipping_print', [$this, 'print_info']);
    }

    public function add_sections($sections)
    {
        $sections['smilepay_shipping'] = __('SmilePay shipping options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'smilepay_shipping') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/settings/admin-settings.php';

            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $setting_idx = array_search(RY_WT::Option_Prefix . 'smilepay_shipping', array_column($settings, 'id'));
                $settings[$setting_idx]['desc'] .= '<br>' . __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
            }
        }

        return $settings;
    }

    public function check_option() {}

    public function add_order_actions($order_actions)
    {
        global $theorder, $post;

        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item) {
            if (false !== RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($item)) {
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

    public function get_code_no()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $logistics_ID = wp_unslash($_GET['id']);

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
            if (isset($shipping_list[$logistics_ID])) {
                if (empty($shipping_list[$logistics_ID]['PaymentNo'])) {
                    RY_WT_WC_SmilePay_Shipping_Api::instance()->get_code_no($order_ID, $logistics_ID);
                }
            }
        }

        wp_safe_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
        exit();
    }

    public function print_info()
    {
        $order_ID = (int) wp_unslash($_GET['id'] ?? 0);
        $logistics_ID = wp_unslash($_GET['id']);

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
            if (isset($shipping_list[$logistics_ID])) {
                if (empty($shipping_list[$logistics_ID]['PaymentNo'])) {
                    RY_WT_WC_SmilePay_Shipping_Api::instance()->get_code_no($order_ID, $logistics_ID);

                    $order = wc_get_order($order_ID);
                    $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                }

                wp_redirect(RY_WT_WC_SmilePay_Shipping_Api::instance()->get_print_url($shipping_list[$logistics_ID]));
                exit();
            }
        }

        wp_safe_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
        exit();
    }
}
