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
        add_action('add_meta_boxes', [$this, 'add_meta_box'], 10, 2);

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_filter('woocommerce_admin_settings_sanitize_option_' . RY_WT::OPTION_PREFIX . 'ecpay_shipping_product_weight', [$this, 'only_number']);
        add_action('woocommerce_update_options_rytools_ecpay_shipping', [$this, 'check_option']);

        add_action('admin_post_ry-print-ecpay-shipping', [$this, 'print_shipping']);
        add_action('wp_ajax_RY_ecpay_shipping_info', [$this, 'get_shipping_info']);
    }

    public function add_meta_box($post_type, $data_object)
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/meta-box.php';
        RY_ECPay_Shipping_Meta_Box::add_meta_box($post_type, $data_object);
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

            if (RY_WT_WC_ECPay_Shipping::instance()->is_testmode()) {
                list($MerchantID, $HashKey, $HashIV, $cvs_type) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();
                $setting_idx = array_search(RY_WT::OPTION_PREFIX . 'ecpay_shipping_MerchantID', array_column($settings, 'id'));
                $settings[$setting_idx]['desc'] = '<p class="description">' . sprintf(
                    /* translators: %s: MerchantID */
                    __('Used MerchantID "%s"', 'ry-woocommerce-tools'),
                    $MerchantID,
                ) . '</p>';
            }
        }

        return $settings;
    }

    public function only_number($value): ?float
    {
        if(null !== $value) {
            $value = (float) $value;
        }

        return $value;
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

        if (empty($logistics_ID)) {
            $get_type = wp_unslash($_GET['type']);
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
                                if ($get_type == 'cvs_711') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'FAMI':
                            case 'FAMIC2C':
                                if ($get_type == 'cvs_family') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'HILIFE':
                            case 'HILIFEC2C':
                                if ($get_type == 'cvs_hilife') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'OKMARTC2C':
                                if ($get_type == 'cvs_ok') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'TCAT':
                                if ($get_type == 'home_tcat') {
                                    $print_list[] = $info;
                                }
                                break;
                            case 'POST':
                                if ($get_type == 'home_post') {
                                    $print_list[] = $info;
                                }
                                break;
                        }
                    }
                }
            }
        } else {
            $order = wc_get_order($order_ID);
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
        }

        if (empty($print_list)) {
            wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
        } else {
            RY_WT_WC_ECPay_Shipping_Api::instance()->get_print_form($print_list);
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
            if(empty($temp)) {
                $temp = null;
            }

            foreach ($order->get_items('shipping') as $item) {
                if (false !== RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($item)) {
                    RY_WT_WC_ECPay_Shipping_Api::instance()->get_code($order, $collection, $temp);
                    break;
                }
            }
        }

        wp_die();
    }
}
