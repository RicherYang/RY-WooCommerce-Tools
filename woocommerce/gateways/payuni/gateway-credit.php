<?php

defined('ABSPATH') or exit;

class RY_PAYUNi_Gateway_Credit extends RY_WT_WC_PAYUNi_Payment_Gateway
{
    public const ID = 'ry_payuni_credit';

    public const PAYMENT_TYPE = 'Credit';

    public const bool SUPPORT_REFUND = true;

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via Credit', 'ry-woocommerce-tools');
        $this->method_title = __('PAYUNi Credit', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via PAYUNi Credit', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/payuni/includes/settings/credit.php';

        parent::__construct();
    }
}
