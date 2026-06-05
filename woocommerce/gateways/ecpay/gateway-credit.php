<?php

defined('ABSPATH') or exit;

class RY_ECPay_Gateway_Credit extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const PAYMENT_TYPE = 'Credit';

    public const bool SUPPORT_REFUNOD = true;

    public $support_applepay = 'yes';

    public function __construct()
    {
        $this->id = 'ry_ecpay_credit';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay Credit ( include "Apple Pay" )', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via ECPay Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/credit.php';

        parent::__construct();

        $this->support_applepay = ! empty($this->settings['applepay']) && 'no' === $this->settings['applepay'] ? 'no' : 'yes';
    }
}
