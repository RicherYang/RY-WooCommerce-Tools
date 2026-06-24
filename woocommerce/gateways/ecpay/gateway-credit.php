<?php

defined('ABSPATH') or exit;

class RY_ECPay_Gateway_Credit extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const ID = 'ry_ecpay_credit';

    public const PAYMENT_TYPE = 'Credit';

    public const SUPPORT_REFUND = true;

    public $support_applepay = 'yes';

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay Credit ( include "Apple Pay" )', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/credit.php';

        parent::__construct();

        $this->support_applepay = ! empty($this->settings['applepay']) && 'no' === $this->settings['applepay'] ? 'no' : 'yes';
    }
}
