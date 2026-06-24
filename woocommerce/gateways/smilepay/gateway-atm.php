<?php

defined('ABSPATH') or exit;

class RY_SmilePay_Gateway_Atm extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const ID = 'ry_smilepay_atm';

    public const PAYMENT_TYPE = '2';

    protected int $check_min_amount = 13;

    protected int $check_max_amount = 20000;

    protected array $check_expire_date = [1, 60];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay ATM', 'ry-woocommerce-tools');

        $this->get_code_mode = true;

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/atm.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 3);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('Bank', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html(rywt_bank_code_to_name($order->get_meta('_smilepay_atm_BankCode'))) . ' (' . esc_html($order->get_meta('_smilepay_atm_BankCode')) . ')</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('ATM Bank account', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_atm_vAccount')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_atm_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
