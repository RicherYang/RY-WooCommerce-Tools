<?php

class RY_ECPay_Gateway_Twqr extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const Payment_Type = 'TWQR';

    protected $check_min_amount = 6;
    protected $check_max_amount = 300000;

    public function __construct()
    {
        $this->id = 'ry_ecpay_twqr';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via TWQR', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay TWQR', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via ECPay TWQR', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/twqr.php';
        $this->init_settings();

        $this->title = $this->get_option('title') ?: $this->method_title;
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        parent::__construct();
    }
}