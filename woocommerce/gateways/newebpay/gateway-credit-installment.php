<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Credit_Installment extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_credit_installment';

    public const PAYMENT_TYPE = 'InstFlag';

    public array $number_of_periods = [];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = true;
        $this->order_button_text = __('Pay via Credit (installment)', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay Credit (installment)', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/credit-installment.php';

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
}
