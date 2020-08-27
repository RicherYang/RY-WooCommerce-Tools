<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

final class RY_SmilePay_Gateway
{
    public static $log_enabled = false;
    public static $log = false;

    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-smilepay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/smilepay-gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/smilepay-gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/smilepay-gateway-base.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-webatm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-cvs-711.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-cvs-fami.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway-barcode.php';

        self::$log_enabled = 'yes' === RY_WT::get_option('smilepay_gateway_log', 'no');

        add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_smilepay_gateway', [__CLASS__, 'check_option']);

        if (is_admin()) {
        } else {
            add_action('woocommerce_thankyou', [__CLASS__, 'payment_info'], 9);
            add_action('woocommerce_view_order', [__CLASS__, 'payment_info'], 9);
        }

        if ('yes' === RY_WT::get_option('smilepay_gateway', 'no')) {
            RY_SmilePay_Gateway_Response::init();

            add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_method']);

            add_action('wp_ajax_RY_SmilePay_getcode', [__CLASS__, 'get_code']);
            add_action('wp_ajax_nopriv_RY_SmilePay_getcode', [__CLASS__, 'get_code']);
            add_action('wp_ajax_RY_SmilePay_shipping_getcode', [__CLASS__, 'shipping_get_code']);
            add_action('wp_ajax_nopriv_RY_SmilePay_shipping_getcode', [__CLASS__, 'shipping_get_code']);
        }
    }

    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, [
                'source' => 'ry_smilepay_gateway',
                '_legacy' => true
            ]);
        }
    }

    public static function add_sections($sections)
    {
        $sections['smilepay_gateway'] = __('SmilePay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'smilepay_gateway') {
            $settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings-smilepay-gateway.php');
        }
        return $settings;
    }

    public static function get_smilepay_api_info()
    {
        if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'yes')) {
            $Dcvc = '107';
            $Rvg2c = '1';
            $Verify_key = '174A02F97A95F72CE301137B3F98D128';
            $Rot_check = '9527';
        } else {
            $Dcvc = RY_WT::get_option('smilepay_gateway_Dcvc');
            $Rvg2c = RY_WT::get_option('smilepay_gateway_Rvg2c');
            $Verify_key = RY_WT::get_option('smilepay_gateway_Verify_key');
            $Rot_check = RY_WT::get_option('smilepay_gateway_Rot_check');
        }

        return [$Dcvc, $Rvg2c, $Verify_key, $Rot_check];
    }

    public static function check_option()
    {
        if ('yes' == RY_WT::get_option('smilepay_gateway', 'no')) {
            if (!extension_loaded('simplexml')) {
                WC_Admin_Settings::add_error(__('SmilePay gateway method need php simplexml extension.', 'ry-woocommerce-tools'));
                RY_WT::update_option('smilepay_gateway', 'no');
            }

            $enable = true;
            if ('yes' !== RY_WT::get_option('smilepay_gateway_testmode', 'yes')) {
                if (empty(RY_WT::get_option('smilepay_gateway_Dcvc'))) {
                    $enable = false;
                }
                if (empty(RY_WT::get_option('smilepay_gateway_Rvg2c'))) {
                    $enable = false;
                }
                if (empty(RY_WT::get_option('smilepay_gateway_Verify_key'))) {
                    $enable = false;
                }
                if (empty(RY_WT::get_option('smilepay_gateway_Rot_check'))) {
                    $enable = false;
                }
            }
            if (!$enable) {
                WC_Admin_Settings::add_error(__('SmilePay gateway method failed to enable!', 'ry-woocommerce-tools'));
                RY_WT::update_option('smilepay_gateway', 'no');
            }
        }
        if ('no' == RY_WT::get_option('smilepay_gateway', 'no')) {
            if ('yes' == RY_WT::get_option('smilepay_shipping', 'no')) {
                WC_Admin_Settings::add_error(__('SmilePay shipping method need enable SmilePay gateway.', 'ry-woocommerce-tools'));
                RY_WT::update_option('smilepay_shipping', 'no');
            }
        }

        if (!preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('smilepay_gateway_order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed allowed', 'ry-woocommerce-tools'));
            RY_WT::update_option('smilepay_gateway_order_prefix', '');
        }
    }

    public static function get_code()
    {
        $order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $order = wc_get_order($order_id);
        $url = false;
        if ($order) {
            $url = RY_SmilePay_Gateway_Api::get_code($order);
        }
        if (!$url) {
            $url = $order->get_checkout_order_received_url();
        }
        echo($url);
        wp_die();
    }

    public static function shipping_get_code()
    {
        $order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $order = wc_get_order($order_id);
        $url = false;
        if ($order) {
            $url = RY_SmilePay_Shipping_Api::get_csv_info($order);
        }
        if (!$url) {
            $url = $order->get_checkout_order_received_url();
        }
        echo($url);
        wp_die();
    }

    public static function payment_info($order_id)
    {
        if (!$order_id) {
            return;
        }

        if (!$order = wc_get_order($order_id)) {
            return;
        }

        switch ($order->get_payment_method()) {
            case 'ry_smilepay_atm':
                $template_file = 'order/order-smilepay-payment-info-atm.php';
                break;
            case 'ry_smilepay_barcode':
                $template_file = 'order/order-smilepay-payment-info-barcode.php';
                break;
            case 'ry_smilepay_cvs_711':
                $template_file = 'order/order-smilepay-payment-info-cvs-711.php';
                break;
            case 'ry_smilepay_cvs_fami':
                $template_file = 'order/order-smilepay-payment-info-cvs-fami.php';
                break;
        }

        if (isset($template_file)) {
            $args = array(
                'order' => $order,
            );
            wc_get_template($template_file, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }
    }

    public static function add_method($methods)
    {
        $methods[] = 'RY_SmilePay_Gateway_Credit';
        $methods[] = 'RY_SmilePay_Gateway_Webatm';
        $methods[] = 'RY_SmilePay_Gateway_Atm';
        $methods[] = 'RY_SmilePay_Gateway_Cvs_711';
        $methods[] = 'RY_SmilePay_Gateway_Cvs_Fami';
        $methods[] = 'RY_SmilePay_Gateway_Barcode';

        return $methods;
    }
}

RY_SmilePay_Gateway::init();
