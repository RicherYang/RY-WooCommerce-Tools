<?php

final class RY_WT_WC_SmilePay_Shipping extends RY_WT_Shipping_Model
{
    public static $support_methods = [
        'ry_smilepay_shipping_cvs_711' => 'RY_SmilePay_Shipping_CVS_711',
        'ry_smilepay_shipping_cvs_fami' => 'RY_SmilePay_Shipping_CVS_Fami',
        'ry_smilepay_shipping_home_tcat' => 'RY_SmilePay_Shipping_Home_Tcat',
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
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/shipping-home-tcat.php';

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
                $chosen_shipping = array_shift($chosen_shipping);
                if (str_contains($chosen_shipping, '_cvs')) {
                    foreach ($_available_gateways as $key => $gateway) {
                        if (str_starts_with($key, 'ry_smilepay_')) {
                            continue;
                        }
                        if ('cod' === $key) {
                            continue;
                        }
                        unset($_available_gateways[$key]);
                    }
                }
            }
        }
        return $_available_gateways;
    }

    public function change_cod_order_status($status, $order)
    {
        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = $this->get_order_support_shipping($shipping_item);
            if ($shipping_method) {
                if (str_contains($shipping_method, '_cvs')) {
                    $status = 'pending';
                    add_filter('woocommerce_payment_successful_result', [$this, 'change_cod_redirect'], 10, 2);
                }
                break;
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
            foreach ($order->get_items('shipping') as $shipping_item) {
                $shipping_method = $this->get_order_support_shipping($shipping_item);
                if ($shipping_method) {
                    RY_WT_WC_SmilePay_Gateway_Api::instance()->checkout_form(wc_get_order($order_ID));
                    break;
                }
            }
        }
    }

    public function shipping_choose_cvs()
    {
        $chosen_shipping = wc_get_chosen_shipping_method_ids();
        $chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
        $this->js_data = [];

        if (count($chosen_shipping)) {
            $chosen_shipping = array_shift($chosen_shipping);
            if (str_contains($chosen_shipping, '_cvs')) {
                $this->js_data['smilepay_cvs'] = true;
            } else {
                $this->js_data['smilepay_home'] = true;
            }
        }
    }

    public function get_code($order_ID, $order)
    {
        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = $this->get_order_support_shipping($shipping_item);
            if ($shipping_method) {
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (0 === count($shipping_list)) {
                    if (str_contains($shipping_method, '_home')) {
                        RY_WT_WC_SmilePay_Shipping_Api::instance()->get_home_info($order_ID);
                    }
                } else {
                    $list = array_filter(array_column($shipping_list, 'PaymentNo'));
                    if (0 === count($list)) {
                        foreach ($shipping_list as $smse_ID => $info) {
                            RY_WT_WC_SmilePay_Shipping_Api::instance()->get_info_no($order_ID, $smse_ID);
                        }
                    }
                }
                break;
            }
        }
    }

    public function shipping_choose_cvs_info($fragments)
    {
        if (!empty($this->js_data)) {
            $fragments['ry_shipping_info'] = $this->js_data;
        }

        return $fragments;
    }

    public function get_order_support_shipping($shipping_item)
    {
        $method_ID = $shipping_item->get_method_id();
        if (isset(self::$support_methods[$method_ID])) {
            return $method_ID;
        }

        return false;
    }
}
