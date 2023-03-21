<?php

final class RY_NewebPay_Gateway
{
    public static $log_enabled = false;
    public static $log = false;

    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-newebpay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/newebpay-gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/newebpay-gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/newebpay-gateway-base.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-webatm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-cvs.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-barcode.php';

        self::$log_enabled = 'yes' === RY_WT::get_option('newebpay_gateway_log', 'no');

        if (is_admin()) {
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_newebpay_gateway', [__CLASS__, 'check_option']);
        } else {
            add_action('woocommerce_thankyou', [__CLASS__, 'payment_info'], 9);
            add_action('woocommerce_view_order', [__CLASS__, 'payment_info'], 9);
        }

        if ('yes' === RY_WT::get_option('newebpay_gateway', 'no')) {
            RY_NewebPay_Gateway_Response::init();

            add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_method']);
        }
    }

    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled || $level == 'error') {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, [
                'source' => 'ry_newebpay_gateway',
                '_legacy' => true
            ]);
        }
    }

    public static function add_sections($sections)
    {
        $sections['newebpay_gateway'] = __('NewebPay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_gateway') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway.php';
        }
        return $settings;
    }

    public static function get_newebpay_api_info()
    {
        $MerchantID = RY_WT::get_option('newebpay_gateway_MerchantID');
        $HashKey = RY_WT::get_option('newebpay_gateway_HashKey');
        $HashIV = RY_WT::get_option('newebpay_gateway_HashIV');

        return [$MerchantID, $HashKey, $HashIV];
    }

    public static function check_option()
    {
        if ('yes' === RY_WT::get_option('newebpay_gateway', 'no')) {
            $enable = true;
            if (empty(RY_WT::get_option('newebpay_gateway_MerchantID'))) {
                $enable = false;
            }
            if (empty(RY_WT::get_option('newebpay_gateway_HashKey'))) {
                $enable = false;
            }
            if (empty(RY_WT::get_option('newebpay_gateway_HashIV'))) {
                $enable = false;
            }
            if (!$enable) {
                WC_Admin_Settings::add_error(__('NewebPay gateway method failed to enable!', 'ry-woocommerce-tools'));
                RY_WT::update_option('newebpay_gateway', 'no');
            }
        }
        if ('no' === RY_WT::get_option('newebpay_gateway', 'no')) {
            if ('yes' === RY_WT::get_option('newebpay_shipping', 'no')) {
                WC_Admin_Settings::add_error(__('NewebPay shipping method need enable NewebPay gateway.', 'ry-woocommerce-tools'));
                RY_WT::update_option('newebpay_shipping', 'no');
            }
        }

        if (!preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('newebpay_gateway_order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed', 'ry-woocommerce-tools'));
            RY_WT::update_option('newebpay_gateway_order_prefix', '');
        }
    }

    public static function payment_info($order_id)
    {
        if (!$order_id) {
            return;
        }

        if (!$order = wc_get_order($order_id)) {
            return;
        }
        $payment_method = $order->get_payment_method();
        switch ($order->get_payment_method()) {
            case 'ry_newebpay_atm':
                $template_file = 'order/order-newebpay-payment-info-atm.php';
                break;
            case 'ry_newebpay_barcode':
                $template_file = 'order/order-newebpay-payment-info-barcode.php';
                break;
            case 'ry_newebpay_cvs':
                $template_file = 'order/order-newebpay-payment-info-cvs.php';
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
        $methods[] = 'RY_NewebPay_Gateway_Credit';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment';
        $methods[] = 'RY_NewebPay_Gateway_Webatm';
        $methods[] = 'RY_NewebPay_Gateway_Atm';
        $methods[] = 'RY_NewebPay_Gateway_Cvc';
        $methods[] = 'RY_NewebPay_Gateway_Barcode';

        return $methods;
    }
}

RY_NewebPay_Gateway::init();
