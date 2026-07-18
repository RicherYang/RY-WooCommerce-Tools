<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Credit extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_credit';

    public const PAYMENT_TYPE = 'CREDIT';

    public bool $support_applepay = false;

    public bool $support_googlepay = false;

    public bool $support_samsungpay = false;

    public bool $support_ae = false;

    public bool $support_union = false;

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/credit.php';

        parent::__construct();

        if (defined('RY_WTP_VERSION')) {
            $this->support_applepay = wc_string_to_bool($this->settings['applepay'] ?? 'no');
            $this->support_googlepay = wc_string_to_bool($this->settings['googlepay'] ?? 'no');
            $this->support_samsungpay = wc_string_to_bool($this->settings['samsungpay'] ?? 'no');
            $this->support_ae = wc_string_to_bool($this->settings['ae'] ?? 'no');
            $this->support_union = wc_string_to_bool($this->settings['union'] ?? 'no');
        }
    }
}
