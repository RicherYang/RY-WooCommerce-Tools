<?php

class RY_ECPay_Gateway_Twqr extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const Payment_Type = 'TWQR';

    protected $check_min_amount = 6;

    protected $check_max_amount = 49999;

    public function __construct()
    {
        $this->id = 'ry_ecpay_twqr';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via TWQR', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay TWQR', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via ECPay TWQR', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/twqr.php';

        parent::__construct();
    }
}
