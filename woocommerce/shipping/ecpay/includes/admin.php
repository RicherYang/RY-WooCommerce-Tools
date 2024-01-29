<?php

final class RY_WT_WC_ECPay_Shipping_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_ECPay_Shipping_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }
        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/ecpay-shipping-meta-box.php';

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_ecpay_shipping', [$this, 'check_option']);

        add_action('add_meta_boxes', ['RY_ECPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        add_action('admin_post_ry-print-ecpay-shipping', [$this, 'print_shipping']);

        add_filter('woocommerce_order_actions', [$this, 'add_order_actions']);
        add_action('woocommerce_order_action_get_new_ecpay_no', [RY_WT_WC_ECPay_Shipping_Api::instance(), 'get_code']);
        add_action('woocommerce_order_action_get_new_ecpay_no_cod', [RY_WT_WC_ECPay_Shipping_Api::instance(), 'get_code_cod']);
        add_action('woocommerce_order_action_send_at_cvs_email', [RY_WT_WC_ECPay_Shipping::instance(), 'send_at_cvs_email']);
    }

    public function add_sections($sections)
    {
        $sections['ecpay_shipping'] = __('ECPay shipping options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'ecpay_shipping') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings/admin-settings.php';

            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $setting_idx = array_search(RY_WT::Option_Prefix . 'ecpay_shipping_cvs_type', array_column($settings, 'id'));
                $settings[$setting_idx]['options'] = [
                    'disable' => _x('Disable', 'Cvs type', 'ry-woocommerce-tools')
                ];
                $settings[$setting_idx]['desc'] = __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
            }
        }

        return $settings;
    }

    public function check_option()
    {
        $name = RY_WT::get_option('ecpay_shipping_sender_name');
        if (mb_strwidth($name) < 1 || mb_strwidth($name) > 10) {
            WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Name length between 1 to 10 letter (5 if chinese)', 'ry-woocommerce-tools'));
            RY_WT::update_option('ecpay_shipping_sender_name', '');
        }
        if (!empty(RY_WT::get_option('ecpay_shipping_sender_phone'))) {
            if (1 !== preg_match('@^\(0\d{1,2}\)\d{6,8}(#\d+)?$@', RY_WT::get_option('ecpay_shipping_sender_phone'))) {
                WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Phone format (0x)xxxxxxx#xx', 'ry-woocommerce-tools'));
                RY_WT::update_option('ecpay_shipping_sender_phone', '');
            }
        }
        if (1 !== preg_match('@^09\d{8}?$@', RY_WT::get_option('ecpay_shipping_sender_cellphone'))) {
            WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Cellphone format 09xxxxxxxx', 'ry-woocommerce-tools'));
            RY_WT::update_option('ecpay_shipping_sender_cellphone', '');
        }
        if (!preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('ecpay_shipping_order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed', 'ry-woocommerce-tools'));
            RY_WT::update_option('ecpay_shipping_order_prefix', '');
        }
    }

    public function print_shipping()
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
                            case 'UNIMARTFREEZE':
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
            RY_WT_WC_ECPay_Shipping_Api::instance()->get_print_form($print_list);
        }
        exit();
    }

    public function add_order_actions($order_actions)
    {
        global $theorder, $post;

        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item) {
            if (false !== RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($item)) {
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
