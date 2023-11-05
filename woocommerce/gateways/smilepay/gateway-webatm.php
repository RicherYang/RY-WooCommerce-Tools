<?php

class RY_SmilePay_Gateway_Webatm extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const Payment_Type = 21;

    protected $check_min_amount = 13;
    protected $check_max_amount = 20000;

    public function __construct()
    {
        $this->id = 'ry_smilepay_webatm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via WebATM', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay WebATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay WebATM', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/webatm.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 7);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        parent::__construct();
    }
}
