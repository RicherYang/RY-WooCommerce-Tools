<?php
final class RY_SmilePay_Shipping
{
    public static $log = false;

    public static $support_methods = [
        'ry_smilepay_shipping_cvs_711' => 'RY_SmilePay_Shipping_CVS_711',
        'ry_smilepay_shipping_cvs_fami' => 'RY_SmilePay_Shipping_CVS_Fami'
    ];

    protected static $js_data;

    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ry-base.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-smilepay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/smilepay-shipping-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/smilepay-shipping-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/smilepay-shipping-base.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/smilepay-shipping-cvs-711.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/smilepay-shipping-cvs-fami.php';

        if ('yes' === RY_WT::get_option('smilepay_shipping', 'no')) {
            RY_SmilePay_Shipping_Response::init();

            add_filter('woocommerce_shipping_methods', [__CLASS__, 'add_method']);

            add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_cvs_info'], 9999);
            add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'only_smilepay_gateway'], 100);
            add_action('woocommerce_checkout_create_order', [__CLASS__, 'save_cvs_info'], 20, 2);
            add_filter('woocommerce_cod_process_payment_order_status', [__CLASS__, 'change_cod_order_status'], 10, 2);
            add_action('woocommerce_receipt_cod', [__CLASS__, 'cod_receipt_page']);
            add_action('woocommerce_review_order_after_shipping', [__CLASS__, 'shipping_choose_cvs']);
            add_filter('woocommerce_update_order_review_fragments', [__CLASS__, 'shipping_choose_cvs_info']);

            if ('yes' === RY_WT::get_option('smilepay_shipping_auto_get_no', 'yes')) {
                add_action('woocommerce_order_status_processing', [__CLASS__, 'get_code'], 10, 2);
            }
        }

        if (is_admin()) {
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_smilepay_shipping', [__CLASS__, 'check_option']);

            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/smilepay-shipping-admin.php';
        }
    }

    public static function log($message, $level = 'info')
    {
        if (RY_SmilePay_Gateway::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, [
                'source' => 'ry_smilepay_shipping',
                '_legacy' => true
            ]);
        }
    }

    public static function add_sections($sections)
    {
        $sections['smilepay_shipping'] = __('SmilePay shipping options', 'ry-woocommerce-tools');
        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'smilepay_shipping') {
            $settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/settings-smilepay-shipping.php');
            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $key = array_search(RY_WT::$option_prefix . 'smilepay_shipping', array_column($settings, 'id'));
                $settings[$key]['desc'] .= '<br>' . __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
            }
        }
        return $settings;
    }

    public static function check_option()
    {
        if (!extension_loaded('simplexml')) {
            WC_Admin_Settings::add_error(__('SmilePay shipping method need php simplexml extension.', 'ry-woocommerce-tools'));
            RY_WT::update_option('smilepay_shipping', 'no');
        }

        if ('yes' == RY_WT::get_option('smilepay_shipping', 'no')) {
            $enable = true;
            if ('no' == RY_WT::get_option('smilepay_gateway', 'no')) {
                WC_Admin_Settings::add_error(__('SmilePay shipping method need enable SmilePay gateway.', 'ry-woocommerce-tools'));
                $enable = false;
            }
            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $enable = false;
            }
            if (!$enable) {
                WC_Admin_Settings::add_error(__('SmilePay shipping method failed to enable!', 'ry-woocommerce-tools'));
                RY_WT::update_option('smilepay_shipping', 'no');
            }
        }
    }

    public static function add_method($shipping_methods)
    {
        $shipping_methods = array_merge($shipping_methods, self::$support_methods);

        return $shipping_methods;
    }

    public static function add_cvs_info($fields)
    {
        $fields['shipping']['shipping_phone'] = [
            'label' => __('Phone', 'ry-woocommerce-tools'),
            'required' => true,
            'type' => 'tel',
            'validate' => ['phone'],
            'class' => ['form-row-wide'],
            'priority' => 100
        ];
        if ('no' == RY_WT::get_option('keep_shipping_phone', 'no')) {
            $fields['shipping']['shipping_phone']['class'][] = 'cvs-info';
        }

        if (is_checkout()) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
            $used_cvs = false;
            if (count($chosen_method)) {
                foreach (self::$support_methods as $method => $method_class) {
                    if ($method && array_key_exists($method, self::$support_methods) && strpos($method, 'cvs') !== false) {
                        $used_cvs = true;
                    }
                }
            }
            if ($used_cvs) {
                foreach ($fields['shipping'] as $key => $filed) {
                    if (isset($filed['class'])) {
                        if (!in_array('cvs-info', $filed['class'])) {
                            if (!in_array($key, ['shipping_first_name', 'shipping_last_name', 'shipping_phone'])) {
                                $fields['shipping'][$key]['class'][] = 'ry-hide';
                            }
                        }
                    } else {
                        if ($filed['type'] != 'hidden') {
                            $fields['shipping'][$key]['class'] = ['ry-hide'];
                        }
                    }
                }
            }
        }

        if (did_action('woocommerce_checkout_process')) {
            $used_cvs = false;
            $shipping_method = isset($_POST['shipping_method']) ? wc_clean($_POST['shipping_method']) : [];
            foreach ($shipping_method as $method) {
                $method = strstr($method, ':', true);
                if ($method && array_key_exists($method, self::$support_methods) && strpos($method, 'cvs') !== false) {
                    $used_cvs = true;
                    break;
                }
            }

            if ($used_cvs) {
                $fields['shipping']['shipping_country']['required'] = false;
                $fields['shipping']['shipping_address_1']['required'] = false;
                $fields['shipping']['shipping_address_2']['required'] = false;
                $fields['shipping']['shipping_city']['required'] = false;
                $fields['shipping']['shipping_state']['required'] = false;
                $fields['shipping']['shipping_postcode']['required'] = false;
                $fields['shipping']['shipping_phone']['required'] = true;
            } else {
                if ('no' == RY_WT::get_option('keep_shipping_phone', 'no')) {
                    $fields['shipping']['shipping_phone']['required'] = false;
                }
            }
        }

        return $fields;
    }

    public static function only_smilepay_gateway($_available_gateways)
    {
        if (WC()->cart && WC()->cart->needs_shipping()) {
            $chosen_shipping = wc_get_chosen_shipping_method_ids();
            $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
            if (count($chosen_shipping)) {
                foreach ($_available_gateways as $key => $gateway) {
                    if (strpos($key, 'ry_smilepay_') === 0) {
                        continue;
                    }
                    if ($key == 'cod') {
                        continue;
                    }
                    unset($_available_gateways[$key]);
                }
            }
        }
        return $_available_gateways;
    }

    public static function save_cvs_info($order, $data)
    {
        if (version_compare(WC_VERSION, '5.6.0', '<')) {
            if (isset($data['shipping_phone'])) {
                $order->update_meta_data('_shipping_phone', $data['shipping_phone']);
            }
        }
    }

    public static function change_cod_order_status($status, $order)
    {
        $items_shipping = $order->get_items('shipping');
        $items_shipping = array_shift($items_shipping);
        if ($items_shipping) {
            if (isset(self::$support_methods[$items_shipping->get_method_id()])) {
                $status = 'pending';
                add_filter('woocommerce_payment_successful_result', [__CLASS__, 'change_cod_redirect'], 10, 2);
            }
        }

        return $status;
    }

    public static function change_cod_redirect($result, $order_id)
    {
        $order = wc_get_order($order_id);
        $result['redirect'] = $order->get_checkout_payment_url(true);

        return $result;
    }

    public static function cod_receipt_page($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            $items_shipping = $order->get_items('shipping');
            $items_shipping = array_shift($items_shipping);
            if ($items_shipping) {
                if (isset(self::$support_methods[$items_shipping->get_method_id()])) {
                    RY_SmilePay_Gateway_Base::receipt_page($order_id);
                }
            }
        }
    }

    public static function shipping_choose_cvs()
    {
        wp_enqueue_script('ry-shipping');
        $chosen_shipping = wc_get_chosen_shipping_method_ids();
        $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
        $chosen_shipping = array_shift($chosen_shipping);
        self::$js_data = [];

        if ($chosen_shipping) {
            self::$js_data['postData'] = [];
        }
    }

    public static function get_code($order_id, $order)
    {
        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        foreach ($shipping_list as $smse_id => $info) {
            RY_SmilePay_Shipping_Api::get_code_no($order_id, $smse_id);
        }
    }

    public static function shipping_choose_cvs_info($fragments)
    {
        if (!empty(self::$js_data)) {
            $fragments['smilepay_shipping_info'] = self::$js_data;
        }

        return $fragments;
    }

    public static function get_order_support_shipping($items)
    {
        foreach (self::$support_methods as $method => $method_class) {
            if (strpos($items->get_method_id(), $method) === 0) {
                return $method;
            }
        }

        return false;
    }
}

RY_SmilePay_Shipping::init();
