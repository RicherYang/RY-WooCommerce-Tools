<?php

class RY_NewebPay_Gateway_Credit_Installment extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const Payment_Type = 'InstFlag';

    public $number_of_periods = '';

    public function __construct()
    {
        $this->id = 'ry_newebpay_credit_installment';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit(installment)', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay Credit(installment)', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay Credit(installment)', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/credit-installment.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);
        $this->number_of_periods = $this->get_option('number_of_periods', []);

        parent::__construct();
    }

    public function is_available()
    {
        $is_available = parent::is_available();
        if($is_available) {
            $is_available = !empty($this->number_of_periods);
        }

        return $is_available;
    }

    public function process_payment($order_ID)
    {
        if (isset($_POST['newebpay_number_of_periods'])) {
            $order = wc_get_order($order_ID);
            $order->update_meta_data('_newebpay_payment_number_of_periods', (int) $_POST['newebpay_number_of_periods']);
            $order->save();
        }

        return parent::process_payment($order_ID);
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
}
