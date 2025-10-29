<?php

class RY_SmilePay_Gateway_Credit extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const Payment_Type = 1;

    public function __construct()
    {
        $this->id = 'ry_smilepay_credit';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay Credit', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/credit.php';

        parent::__construct();
    }
}
