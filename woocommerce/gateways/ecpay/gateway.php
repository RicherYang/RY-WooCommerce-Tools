<?php

final class RY_WT_WC_ECPay_Gateway extends RY_WT_WC_Model
{
    protected static $_instance = null;

    protected $log_source = 'ry_ecpay_gateway';

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

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-barcode.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-cvs.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-twqr.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway-webatm.php';

        $this->log_enabled = 'yes' === RY_WT::get_option('ecpay_gateway_log', 'no');
        $this->testmode = 'yes' === RY_WT::get_option('ecpay_gateway_testmode', 'no');

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
        $methods[] = 'RY_ECPay_Gateway_Twqr';
        $methods[] = 'RY_ECPay_Gateway_Webatm';

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
            case 'ry_ecpay_atm':
                $template_file = 'order/order-ecpay-payment-info-atm.php';
                break;
            case 'ry_ecpay_barcode':
                $template_file = 'order/order-ecpay-payment-info-barcode.php';
                break;
            case 'ry_ecpay_bnpl':
                $template_file = 'order/order-ecpay-payment-info-bnpl.php';
                break;
            case 'ry_ecpay_cvs':
                $template_file = 'order/order-ecpay-payment-info-cvs.php';
                break;
        }

        if (isset($template_file)) {
            $args = [
                'order' => $order
            ];
            wc_get_template($template_file, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }
    }

    public function get_api_info()
    {
        $MerchantID = RY_WT::get_option('ecpay_gateway_MerchantID');
        if ($this->testmode) {
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
            $HashKey = RY_WT::get_option('ecpay_gateway_HashKey');
            $HashIV = RY_WT::get_option('ecpay_gateway_HashIV');
        }

        return [$MerchantID, $HashKey, $HashIV];
    }
}
