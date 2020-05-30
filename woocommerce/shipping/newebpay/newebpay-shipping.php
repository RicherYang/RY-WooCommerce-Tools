<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

final class RY_NewebPay_Shipping
{
    public static $support_methods = [
        'ry_newebpay_shipping_cvs' => 'RY_NewebPay_Shipping_CVS'
    ];

    protected static $js_data;

    public static function init()
    {
        include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ry-base.php');
        include_once(RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-newebpay.php');
        include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/newebpay-shipping-cvs.php');

        if ('yes' === RY_WT::get_option('newebpay_shipping', 'no')) {
            add_filter('woocommerce_shipping_methods', [__CLASS__, 'add_method']);

            add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_cvs_info']);
            add_action('woocommerce_checkout_process', [__CLASS__, 'is_need_checkout_fields']);
            add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'only_newebpay_gateway'], 100);
            add_filter('woocommerce_cod_process_payment_order_status', [__CLASS__, 'change_cod_order_status'], 10, 2);
            add_action('woocommerce_receipt_cod', [__CLASS__, 'cod_receipt_page']);

            add_action('woocommerce_review_order_after_shipping', [__CLASS__, 'shipping_choose_cvs']);
            add_filter('woocommerce_update_order_review_fragments', [__CLASS__, 'shipping_choose_cvs_info']);
        }

        if (is_admin()) {
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_newebpay_shipping', [__CLASS__, 'check_option']);

            include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/newebpay-shipping-admin.php');
        }
    }

    public static function add_sections($sections)
    {
        $sections['newebpay_shipping'] = __('NewebPay shipping options', 'ry-woocommerce-tools');
        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_shipping') {
            $settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/settings-newebpay-shipping.php');
            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $key = array_search(RY_WT::$option_prefix . 'newebpay_shipping', array_column($settings, 'id'));
                $settings[$key]['desc'] .= '<br>' . __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
            }
        }
        return $settings;
    }

    public static function check_option()
    {
        $enable = true;
        if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
            $enable = false;
        }
        if ('no' == RY_WT::get_option('newebpay_gateway', 'no')) {
            WC_Admin_Settings::add_error(__('NewebPay shipping method need enable NewebPay gateway.', 'ry-woocommerce-tools'));
            $enable = false;
        }
        if (!$enable) {
            WC_Admin_Settings::add_error(__('NewebPay shipping method failed to enable!', 'ry-woocommerce-tools'));
            RY_WT::update_option('newebpay_shipping', 'no');
        }
    }

    public static function add_method($shipping_methods)
    {
        $shipping_methods = array_merge($shipping_methods, self::$support_methods);

        return $shipping_methods;
    }

    public static function add_cvs_info($fields)
    {
        if (is_checkout()) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
            $is_support = false;
            if (count($chosen_method)) {
                foreach (self::$support_methods as $method => $method_class) {
                    if (strpos($chosen_method[0], $method) === 0) {
                        $is_support = true;
                    }
                }
            }
            if ($is_support) {
                foreach ($fields['shipping'] as $key => $filed) {
                    if (isset($filed['class'])) {
                        $fields['shipping'][$key]['class'][] = 'ry-hide';
                    } else {
                        if ($filed['type'] != 'hidden') {
                            $fields['shipping'][$key]['class'] = ['ry-hide'];
                        }
                    }
                }
            }
        }

        return $fields;
    }

    public static function is_need_checkout_fields()
    {
        $used_cvs = false;
        $shipping_method = isset($_POST['shipping_method']) ? wc_clean($_POST['shipping_method']) : [];
        foreach ($shipping_method as $method) {
            $method = strstr($method, ':', true);
            if ($method && array_key_exists($method, self::$support_methods)) {
                $used_cvs = true;
                break;
            }
        }

        if ($used_cvs) {
            add_filter('woocommerce_checkout_fields', [__CLASS__, 'fix_add_cvs_info'], 9999);
        }
    }

    public static function fix_add_cvs_info($fields)
    {
        $fields['shipping']['shipping_first_name']['required'] = false;
        $fields['shipping']['shipping_last_name']['required'] = false;
        $fields['shipping']['shipping_country']['required'] = false;
        $fields['shipping']['shipping_address_1']['required'] = false;
        $fields['shipping']['shipping_address_2']['required'] = false;
        $fields['shipping']['shipping_city']['required'] = false;
        $fields['shipping']['shipping_state']['required'] = false;
        $fields['shipping']['shipping_postcode']['required'] = false;
        return $fields;
    }

    public function only_newebpay_gateway($_available_gateways)
    {
        if (WC()->cart && WC()->cart->needs_shipping()) {
            $chosen_shipping = wc_get_chosen_shipping_method_ids();
            $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
            if (count($chosen_shipping)) {
                foreach ($_available_gateways as $key => $gateway) {
                    if (strpos($key, 'ry_newebpay_') === 0) {
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

    public function change_cod_order_status($status, $order)
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

    public function change_cod_redirect($result, $order_id)
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
                    RY_NewebPay_Gateway_Api::checkout_form($order, wc_get_payment_gateway_by_order($order));
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

    public static function shipping_choose_cvs_info($fragments)
    {
        if (!empty(self::$js_data)) {
            $fragments['newebpay_shipping_info'] = self::$js_data;
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

RY_NewebPay_Shipping::init();
