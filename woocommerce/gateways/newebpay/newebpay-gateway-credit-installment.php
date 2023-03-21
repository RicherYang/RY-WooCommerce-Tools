<?php

class RY_NewebPay_Gateway_Credit_Installment extends RY_NewebPay_Gateway_Base
{
    public $number_of_periods = '';

    public $payment_type = 'InstFlag';

    public function __construct()
    {
        $this->id = 'ry_newebpay_credit_installment';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit(installment)', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay Credit(installment)', 'ry-woocommerce-tools');
        $this->method_description = '';

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway-credit-installment.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', $this->check_min_amount);
        $this->number_of_periods = $this->get_option('number_of_periods', []);

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' === $this->enabled && WC()->cart) {
            if (empty($this->number_of_periods)) {
                return false;
            }
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function payment_fields()
    {
        parent::payment_fields();
        if (is_array($this->number_of_periods)) {
            echo '<p>' . _x('Number of periods', 'Checkout info', 'ry-woocommerce-tools');
            echo ' <select name="newebpay_number_of_periods">';
            foreach ($this->number_of_periods as $number_of_periods) {
                echo '<option value="' . $number_of_periods . '">' . $number_of_periods . '</option>';
            }
            echo '</select>';
        }
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via NewebPay Credit(installment)', 'ry-woocommerce-tools'));
        if (isset($_POST['newebpay_number_of_periods'])) {
            $order->update_meta_data('_newebpay_payment_number_of_periods', (int) $_POST['newebpay_number_of_periods']);
        }
        $order->save();
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
