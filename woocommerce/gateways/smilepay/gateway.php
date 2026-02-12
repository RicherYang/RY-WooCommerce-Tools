<?php

final class RY_WT_WC_SmilePay_Gateway extends RY_WT_Model
{
    protected static $_instance = null;

    protected $model_type = 'smilepay_gateway';

    public static function instance(): RY_WT_WC_SmilePay_Gateway
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api-smilepay.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/ajax.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-barcode.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-cvs-711.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-cvs-fami.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway-webatm.php';

        RY_WT_WC_Gateways::instance();
        RY_WT_WC_SmilePay_Gateway_Ajax::instance();
        RY_WT_WC_SmilePay_Gateway_Response::instance();

        add_filter('woocommerce_payment_gateways', [$this, 'add_method']);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/admin.php';
            RY_WT_WC_SmilePay_Gateway_Admin::instance();
        } else {
            add_action('woocommerce_thankyou', [$this, 'payment_info'], 9);
            add_action('woocommerce_view_order', [$this, 'payment_info'], 9);
        }
    }

    public function add_method($methods)
    {
        $methods[] = 'RY_SmilePay_Gateway_Atm';
        $methods[] = 'RY_SmilePay_Gateway_Barcode';
        $methods[] = 'RY_SmilePay_Gateway_Credit';
        $methods[] = 'RY_SmilePay_Gateway_Cvs_711';
        $methods[] = 'RY_SmilePay_Gateway_Cvs_Fami';
        $methods[] = 'RY_SmilePay_Gateway_Webatm';

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
            $args = [
                'order' => $order,
            ];
            wc_get_template($template_file, $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        }
    }

    public function get_api_info()
    {
        if ($this->is_testmode()) {
            $Dcvc = '107';
            $Rvg2c = '1';
            $Verify_key = '174A02F97A95F72CE301137B3F98D128';
            $Rot_check = '1111';
        } else {
            $Dcvc = RY_WT::get_option('smilepay_gateway_Dcvc');
            $Rvg2c = RY_WT::get_option('smilepay_gateway_Rvg2c');
            $Verify_key = RY_WT::get_option('smilepay_gateway_Verify_key');
            $Rot_check = RY_WT::get_option('smilepay_gateway_Rot_check');
        }

        return [$Dcvc, $Rvg2c, $Verify_key, $Rot_check];
    }
}
