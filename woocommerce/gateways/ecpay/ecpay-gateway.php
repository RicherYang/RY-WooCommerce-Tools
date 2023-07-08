<?php

final class RY_ECPay_Gateway
{
    public static $log_enabled = false;
    public static $log = false;

    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/ecpay-gateway-base.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-webatm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-cvs.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway-barcode.php';

        self::$log_enabled = 'yes' === RY_WT::get_option('ecpay_gateway_log', 'no');

        if (is_admin()) {
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_ecpay_gateway', [__CLASS__, 'check_option']);
        } else {
            add_action('woocommerce_thankyou', [__CLASS__, 'payment_info'], 9);
            add_action('woocommerce_view_order', [__CLASS__, 'payment_info'], 9);
        }

        if ('yes' === RY_WT::get_option('ecpay_gateway', 'no')) {
            RY_ECPay_Gateway_Response::init();

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
                'source' => 'ry_ecpay_gateway',
                '_legacy' => true
            ]);
        }
    }

    public static function add_sections($sections)
    {
        $sections['ecpay_gateway'] = __('ECPay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'ecpay_gateway') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway.php';

            $checkout_page_id = wc_get_page_id('checkout');
            if($checkout_page_id && has_block('woocommerce/checkout', $checkout_page_id)) {
                echo '<div class="notice notice-info"><p><strong>' . esc_html__('NOT test can work with WooCommerce checkout block! Shortcode are recommended.', 'ry-woocommerce-tools') . '</strong></p></div>';
            }

            if (!defined('RY_WEI_VERSION')) {
                $settings[1]['desc'] .= sprintf(
                    __('<p>If you need ECPay Invoice support, you can try %s</p>', 'ry-woocommerce-tools'),
                    '<a href="https://ry-plugin.com/ry-woocommerce-ecpay-invoice">RY WooCommerce ECPay Invoice</a>'
                );
            }
        }

        return $settings;
    }

    public static function get_ecpay_api_info()
    {
        if ('yes' === RY_WT::get_option('ecpay_gateway_testmode', 'no')) {
            $MerchantID = RY_WT::get_option('ecpay_gateway_MerchantID');
            switch($MerchantID) {
                case '3002607':
                    $HashKey = 'pwFHCqoQZGmho4w6';
                    $HashIV = 'EkRm7iFT261dpevs';
                    break;
                case '2000132':
                default:
                    $MerchantID = '2000132';
                    $HashKey = '5294y06JbISpM5x9';
                    $HashIV = 'v77hoKGq4kWxNNIS';
                    break;
            }


        } else {
            $MerchantID = RY_WT::get_option('ecpay_gateway_MerchantID');
            $HashKey = RY_WT::get_option('ecpay_gateway_HashKey');
            $HashIV = RY_WT::get_option('ecpay_gateway_HashIV');
        }

        return [$MerchantID, $HashKey, $HashIV];
    }

    public static function check_option()
    {
        if ('yes' === RY_WT::get_option('ecpay_gateway', 'no')) {
            $enable = true;
            if ('yes' !== RY_WT::get_option('ecpay_gateway_testmode', 'no')) {
                if (empty(RY_WT::get_option('ecpay_gateway_MerchantID'))) {
                    $enable = false;
                }
                if (empty(RY_WT::get_option('ecpay_gateway_HashKey'))) {
                    $enable = false;
                }
                if (empty(RY_WT::get_option('ecpay_gateway_HashIV'))) {
                    $enable = false;
                }
            }
            if (!$enable) {
                WC_Admin_Settings::add_error(__('ECPay gateway method failed to enable!', 'ry-woocommerce-tools'));
                RY_WT::update_option('ecpay_gateway', 'no');
            }
        }
        if (!preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('ecpay_gateway_order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed', 'ry-woocommerce-tools'));
            RY_WT::update_option('ecpay_gateway_order_prefix', '');
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
        switch ($order->get_payment_method()) {
            case 'ry_ecpay_atm':
                $template_file = 'order/order-ecpay-payment-info-atm.php';
                break;
            case 'ry_ecpay_barcode':
                $template_file = 'order/order-ecpay-payment-info-barcode.php';
                break;
            case 'ry_ecpay_cvs':
                $template_file = 'order/order-ecpay-payment-info-cvs.php';
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
        $methods[] = 'RY_ECPay_Gateway_Credit';
        $methods[] = 'RY_ECPay_Gateway_Credit_Installment';
        $methods[] = 'RY_ECPay_Gateway_Webatm';
        $methods[] = 'RY_ECPay_Gateway_Atm';
        $methods[] = 'RY_ECPay_Gateway_Cvc';
        $methods[] = 'RY_ECPay_Gateway_Barcode';

        return $methods;
    }
}

RY_ECPay_Gateway::init();
