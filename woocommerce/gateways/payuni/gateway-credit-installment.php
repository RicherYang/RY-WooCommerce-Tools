<?php

defined('ABSPATH') or exit;

class RY_PAYUNi_Gateway_Credit_Installment extends RY_WT_WC_PAYUNi_Payment_Gateway
{
    public const PAYMENT_TYPE = 'CreditInst';

    public const bool SUPPORT_REFUND = true;

    public $number_of_periods = [];

    public function __construct()
    {
        $this->id = 'ry_payuni_credit_installment';
        $this->has_fields = true;
        $this->order_button_text = __('Pay via Credit(installment)', 'ry-woocommerce-tools');
        $this->method_title = __('PAYUNi Credit(installment)', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via PAYUNi Credit(installment)', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/settings/credit-installment.php';

        parent::__construct();

        $this->number_of_periods = (array) ($this->settings['number_of_periods'] ?? []);
        $this->number_of_periods = array_filter(array_map('intval', $this->number_of_periods));
    }

    public function is_available()
    {
        $is_available = parent::is_available();
        if ($is_available) {
            $is_available = !empty($this->number_of_periods);
        }

        return $is_available;
    }

    public function process_payment($order_ID)
    {
        if (isset($_POST['payuni_number_of_periods'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $order = wc_get_order($order_ID);
            $order->update_meta_data('_payuni_payment_number_of_periods', intval($_POST['payuni_number_of_periods'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $order->save();
        }

        return parent::process_payment($order_ID);
    }

    public function payment_fields()
    {
        parent::payment_fields();

        if (is_array($this->number_of_periods)) {
            echo '<p>' . esc_html_x('Number of periods', 'Checkout info', 'ry-woocommerce-tools');
            echo ' <select name="payuni_number_of_periods">';
            foreach ($this->number_of_periods as $number_of_periods) {
                echo '<option value="' . esc_attr($number_of_periods) . '">' . esc_html($number_of_periods) . '</option>';
            }
            echo '</select></p>';
        }
    }
}
