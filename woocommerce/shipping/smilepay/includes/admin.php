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
        add_action('add_meta_boxes', [$this, 'add_meta_box'], 10, 2);

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_smilepay_shipping', [$this, 'check_option']);

        add_action('add_meta_boxes', ['RY_SmilePay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        add_action('admin_post_ry-print-smilepay-shipping', [$this, 'print_shipping']);
        add_action('wp_ajax_RY_smilepay_shipping_info', [$this, 'get_shipping_info']);
        add_action('wp_ajax_RY_smilepay_shipping_no', [$this, 'get_code_no']);
    }

    public function add_meta_box($post_type, $data_object)
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/meta-box.php';
        RY_SmilePay_Shipping_Meta_Box::add_meta_box($post_type, $data_object);
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
        }

        return $settings;
    }

    public function check_option() {}

    public function print_shipping()
    {
        $order_ID = wp_unslash($_GET['orderid'] ?? '');
        $logistics_ID = wp_unslash($_GET['id'] ?? 0);
        $print_list = [];
        $print_type = '';

        if (empty($logistics_ID)) {
            $get_type = wp_unslash($_GET['type']);
            $order_IDs = explode(',', $order_ID);
            foreach ($order_IDs as $order_ID) {
                $order = wc_get_order((int) $order_ID);
                if (empty($order)) {
                    continue;
                }
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (is_array($shipping_list)) {
                    foreach ($shipping_list as $info) {
                        switch ($info['type']) {
                            case '7NET':
                                if ($get_type == 'cvs_711') {
                                    $print_list[] = $info['PaymentNo'] . $info['ValidationNo'];
                                    $print_type = $info['type'];
                                }
                                break;
                            case 'FAMI':
                                if ($get_type == 'cvs_fami') {
                                    $print_list[] = $info['PaymentNo'] . $info['ValidationNo'];
                                    $print_type = $info['type'];
                                }
                                break;
                        }
                    }
                }
            }
        } else {
            $order = wc_get_order($order_ID);
            if (!empty($order)) {
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (is_array($shipping_list)) {
                    foreach ($shipping_list as $info) {
                        if ($info['ID'] == $logistics_ID) {
                            $print_list[] = $info['PaymentNo'] . $info['ValidationNo'];
                            $print_type = $info['type'];
                        }
                    }
                }
            }
        }

        if (empty($print_list)) {
            wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
        } else {
            RY_WT_WC_SmilePay_Shipping_Api::instance()->get_print_url($print_list, $print_type);
        }
        exit();
    }

    public function get_shipping_info()
    {
        check_ajax_referer('get-shipping-info');

        $order_ID = (int) wp_unslash($_POST['orderid'] ?? 0);

        $order = wc_get_order($order_ID);
        if (!empty($order)) {
            $collection = 'Y' === wp_unslash($_POST['collection'] ?? '');
            $temp = substr(wp_unslash($_POST['temp'] ?? ''), 0, 1);
            if (empty($temp)) {
                $temp = null;
            }

            foreach ($order->get_items('shipping') as $item) {
                if (false !== RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($item)) {
                    $url = RY_WT_WC_SmilePay_Shipping_Api::instance()->get_admin_csv_info($order, $collection);
                    echo $url;
                }
            }
        }

        wp_die();
    }

    public function get_code_no()
    {
        check_ajax_referer('smilepay-shipping-no');

        $order_ID = (int) wp_unslash($_POST['orderid'] ?? 0);
        $logistics_ID = wp_unslash($_POST['id'] ?? '');

        $order = wc_get_order($order_ID);
        if (!empty($order)) {
            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (is_array($shipping_list)) {
                foreach ($shipping_list as $info) {
                    if ($info['ID'] == $logistics_ID) {
                        if (empty($shipping_list[$logistics_ID]['PaymentNo'])) {
                            RY_WT_WC_SmilePay_Shipping_Api::instance()->get_code_no($order_ID, $info['ID']);
                        }
                    }
                }
            }
        }

        wp_die();
    }
}
