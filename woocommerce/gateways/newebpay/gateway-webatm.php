<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Webatm extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_webatm';

    public const PAYMENT_TYPE = 'WEBATM';

    protected int $check_min_amount = 16;

    protected int $check_max_amount = 49999;

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via WebATM', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay WebATM', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/webatm.php';

        parent::__construct();
    }
}
