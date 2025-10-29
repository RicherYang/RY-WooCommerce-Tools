<?php

class RY_NewebPay_Gateway_Webatm extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const Payment_Type = 'WEBATM';

    public function __construct()
    {
        $this->id = 'ry_newebpay_webatm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via WebATM', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay WebATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay WebATM', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/webatm.php';

        parent::__construct();
    }
}
