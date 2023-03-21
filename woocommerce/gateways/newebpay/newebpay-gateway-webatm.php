<?php

class RY_NewebPay_Gateway_Webatm extends RY_NewebPay_Gateway_Base
{
    public $payment_type = 'WEBATM';

    public function __construct()
    {
        $this->id = 'ry_newebpay_webatm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via WebATM', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay WebATM', 'ry-woocommerce-tools');
        $this->method_description = '';

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway-webatm.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', $this->check_min_amount);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' === $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    return false;
                }
                if ($this->max_amount > 0 && $total > $this->max_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via NewebPay WebATM', 'ry-woocommerce-tools'));
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
