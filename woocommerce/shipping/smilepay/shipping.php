<?php

final class RY_WT_WC_SmilePay_Shipping extends RY_WT_Shipping_Model
{
    public static $support_methods = [
        'ry_smilepay_shipping_cvs_711' => 'RY_SmilePay_Shipping_CVS_711',
        'ry_smilepay_shipping_cvs_fami' => 'RY_SmilePay_Shipping_CVS_Fami',
    ];

    protected static $_instance = null;

    protected $js_data;

    protected $model_type = 'smilepay_shipping';

    public static function instance(): RY_WT_WC_SmilePay_Shipping
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

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/shipping-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/shipping-response.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/shipping-method.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/shipping-cvs-711.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/shipping-cvs-fami.php';

        RY_WT_WC_Shipping::instance();
        RY_WT_WC_SmilePay_Shipping_Response::instance();

        add_filter('woocommerce_shipping_methods', [$this, 'add_method']);

        add_filter('woocommerce_checkout_fields', [$this, 'add_cvs_info'], 9999);
        add_filter('woocommerce_available_payment_gateways', [$this, 'only_smilepay_gateway'], 100);
        add_filter('woocommerce_cod_process_payment_order_status', [$this, 'change_cod_order_status'], 10, 2);
        add_action('woocommerce_receipt_cod', [$this, 'cod_receipt_page']);

        add_filter('woocommerce_update_order_review_fragments', [$this, 'shipping_choose_cvs_info']);

        if ('yes' === RY_WT::get_option('smilepay_shipping_auto_get_no', 'yes')) {
            add_action('woocommerce_order_status_processing', [$this, 'get_code'], 10, 2);
        }

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/admin.php';
            RY_WT_WC_SmilePay_Shipping_Admin::instance();
        } else {
            add_action('woocommerce_review_order_after_shipping', [$this, 'shipping_choose_cvs']);
        }
    }

    public function add_method($shipping_methods)
    {
        return array_merge($shipping_methods, self::$support_methods);
    }

    public function only_smilepay_gateway($_available_gateways)
    {
        if (WC()->cart && WC()->cart->needs_shipping()) {
            $chosen_shipping = wc_get_chosen_shipping_method_ids();
            $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
            if (count($chosen_shipping)) {
                foreach ($_available_gateways as $key => $gateway) {
                    if (0 === strpos($key, 'ry_smilepay_')) {
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
                add_filter('woocommerce_payment_successful_result', [$this, 'change_cod_redirect'], 10, 2);
            }
        }

        return $status;
    }

    public function change_cod_redirect($result, $order_ID)
    {
        $order = wc_get_order($order_ID);
        $result['redirect'] = $order->get_checkout_payment_url(true);

        return $result;
    }

    public function cod_receipt_page($order_ID)
    {
        if ($order = wc_get_order($order_ID)) {
            $items_shipping = $order->get_items('shipping');
            $items_shipping = array_shift($items_shipping);
            if ($items_shipping) {
                if (isset(self::$support_methods[$items_shipping->get_method_id()])) {
                    RY_WT_WC_SmilePay_Gateway_Api::instance()->checkout_form(wc_get_order($order_ID));
                }
            }
        }
    }

    public function shipping_choose_cvs()
    {
        $chosen_shipping = wc_get_chosen_shipping_method_ids();
        $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
        $chosen_shipping = array_shift($chosen_shipping);
        $this->js_data = [];

        if ($chosen_shipping) {
            $this->js_data['smilepay_cvs'] = true;
        }
    }

    public function get_code($order_ID, $order)
    {
        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        foreach ($shipping_list as $smse_ID => $info) {
            RY_WT_WC_SmilePay_Shipping_Api::instance()->get_code_no($order_ID, $smse_ID);
        }
    }

    public function shipping_choose_cvs_info($fragments)
    {
        if (!empty($this->js_data)) {
            $fragments['ry_shipping_info'] = $this->js_data;
        }

        return $fragments;
    }

    public function get_order_support_shipping($items)
    {
        foreach (self::$support_methods as $method => $method_class) {
            if (0 === strpos($items->get_method_id(), $method)) {
                return $method;
            }
        }

        return false;
    }
}
