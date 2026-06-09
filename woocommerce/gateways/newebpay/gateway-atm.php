<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Atm extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_atm';

    public const PAYMENT_TYPE = 'VACC';

    protected int $check_min_amount = 16;

    protected int $check_max_amount = 49999;

    protected array $check_expire_date = [1, 180];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay ATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay ATM', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/atm.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 7);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('Bank', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html(rywt_bank_code_to_name($order->get_meta('_newebpay_atm_BankCode'))) . ' (' . esc_html($order->get_meta('_newebpay_atm_BankCode')) . ')</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('ATM Bank account', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_atm_vAccount')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_atm_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
