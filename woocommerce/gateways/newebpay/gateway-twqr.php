<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Twqr extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_twqr';

    public const PAYMENT_TYPE = 'TWQR';

    protected int $check_min_amount = 30;

    protected int $check_max_amount = 20000;

    protected array $check_expire_date = [1, 180];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via TWQR', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay TWQR', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay TWQR', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/twqr.php';

        parent::__construct();
    }
}
