<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_PAYUNi_Gateway extends RY_WT_Model
{
    protected static ?self $_instance = null;

    protected string $model_type = 'payuni_gateway';

    public static function instance(): RY_WT_WC_PAYUNi_Gateway
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api-payuni.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/gateway-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/gateway-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/gateway-atm.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/gateway-credit-installment.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/gateway-credit.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/gateway-cvs.php';

        RY_WT_WC_Gateways::instance();
        RY_WT_WC_PAYUNi_Gateway_Response::instance();

        add_filter('woocommerce_payment_gateways', [$this, 'add_method']);

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/admin.php';
            RY_WT_WC_PAYUNi_Gateway_Admin::instance();
        } else {
            add_action('woocommerce_thankyou', [$this, 'payment_info'], 9);
            add_action('woocommerce_view_order', [$this, 'payment_info'], 9);
        }
    }

    public function add_method($methods)
    {
        $methods[] = 'RY_PAYUNi_Gateway_Atm';
        $methods[] = 'RY_PAYUNi_Gateway_Credit_Installment';
        $methods[] = 'RY_PAYUNi_Gateway_Credit';
        $methods[] = 'RY_PAYUNi_Gateway_Cvs';

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
            case 'ry_payuni_atm':
                $template_file = 'order/order-payuni-payment-info-atm.php';
                break;
            case 'ry_payuni_cvs':
                $template_file = 'order/order-payuni-payment-info-cvs.php';
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
        $MerID = (string) RY_WT::get_option('payuni_gateway_MerID');
        $HashKey = (string) RY_WT::get_option('payuni_gateway_HashKey');
        $HashIV = (string) RY_WT::get_option('payuni_gateway_HashIV');

        return [$MerID, $HashKey, $HashIV];
    }
}
