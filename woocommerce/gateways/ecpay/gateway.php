<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_ECPay_Gateway extends RY_WT_Gateway_Model
{
    protected static ?self $_instance = null;

    protected string $model_type = 'ecpay_gateway';

    public static function instance(): RY_WT_WC_ECPay_Gateway
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api-ecpay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-barcode.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-cvs.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-webatm.php';

        RY_WT_WC_Gateways::instance();
        RY_WT_WC_ECPay_Gateway_Response::instance();

        add_filter('woocommerce_payment_gateways', [$this, 'add_method']);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/admin.php';
            RY_WT_WC_ECPay_Gateway_Admin::instance();
        } else {
            add_action('woocommerce_thankyou', [$this, 'payment_info'], 9);
            add_action('woocommerce_view_order', [$this, 'payment_info'], 9);
        }
    }

    public function add_method($methods)
    {
        $methods[] = 'RY_ECPay_Gateway_Atm';
        $methods[] = 'RY_ECPay_Gateway_Barcode';
        $methods[] = 'RY_ECPay_Gateway_Credit_Installment';
        $methods[] = 'RY_ECPay_Gateway_Credit';
        $methods[] = 'RY_ECPay_Gateway_Cvs';
        $methods[] = 'RY_ECPay_Gateway_Webatm';

        return $methods;
    }

    public function get_api_info()
    {
        $api_info = RY_WT::get_option('ecpay_gateway_apiinfo', []);
        if (!is_array($api_info)) {
            $api_info = [];
        }
        $api_info = array_merge([
            'prefix' => '',
            'itemname' => '',
            'testmode' => 'no',
            'MerchantID' => '',
            'HashKey' => '',
            'HashIV' => '',
        ], $api_info);
        $api_info['testmode'] = wc_string_to_bool($api_info['testmode']);

        if ($api_info['testmode'] === true) {
            $api_info['MerchantID'] = '3002607';
            $api_info['HashKey'] = 'pwFHCqoQZGmho4w6';
            $api_info['HashIV'] = 'EkRm7iFT261dpevs';
        }

        return $api_info;
    }
}
