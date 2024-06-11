<?php

final class RY_WT_WC_NewebPay_Gateway extends RY_WT_WC_Model
{
    protected static $_instance = null;

    protected $model_type = 'newebpay_gateway';

    public static function instance(): RY_WT_WC_NewebPay_Gateway
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api-newebpay.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-barcode.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-cvs.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway-webatm.php';

        RY_WT_WC_NewebPay_Gateway_Response::instance();

        add_filter('woocommerce_payment_gateways', [$this, 'add_method']);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/admin.php';
            RY_WT_WC_NewebPay_Gateway_Admin::instance();
        } else {
            add_action('woocommerce_thankyou', [$this, 'payment_info'], 9);
            add_action('woocommerce_view_order', [$this, 'payment_info'], 9);
        }
    }

    public function add_method($methods)
    {
        $methods[] = 'RY_NewebPay_Gateway_Atm';
        $methods[] = 'RY_NewebPay_Gateway_Barcode';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment';
        $methods[] = 'RY_NewebPay_Gateway_Credit';
        $methods[] = 'RY_NewebPay_Gateway_Cvs';
        $methods[] = 'RY_NewebPay_Gateway_Webatm';

        return $methods;
    }

    public function payment_info($order_ID)
    {
        if (!$order_ID) {
            return;
        }
        if (!$order = wc_get_order($order_ID)) {
            return;
        }

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
            $args = [
                'order' => $order,
            ];
            wc_get_template($template_file, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }
    }

    public function get_api_info()
    {
        $MerchantID = RY_WT::get_option('newebpay_gateway_MerchantID');
        $HashKey = RY_WT::get_option('newebpay_gateway_HashKey');
        $HashIV = RY_WT::get_option('newebpay_gateway_HashIV');

        return [$MerchantID, $HashKey, $HashIV];
    }
}
