<?php

defined('ABSPATH') or exit;

class RY_ECPay_Gateway_Credit extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const ID = 'ry_ecpay_credit';

    public const PAYMENT_TYPE = 'Credit';

    public const SUPPORT_REFUND = true;

    public bool $support_applepay = false;

    public bool $support_union = false;

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/credit.php';

        parent::__construct();

        if (defined('RY_WTP_VERSION')) {
            $this->support_applepay = wc_string_to_bool($this->settings['applepay'] ?? 'no');
            $this->support_union = wc_string_to_bool($this->settings['union'] ?? 'no');
        }
    }
}
