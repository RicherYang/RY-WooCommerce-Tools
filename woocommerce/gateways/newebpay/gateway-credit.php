<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Credit extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_credit';

    public const PAYMENT_TYPE = 'CREDIT';

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/credit.php';

        parent::__construct();
    }
}
