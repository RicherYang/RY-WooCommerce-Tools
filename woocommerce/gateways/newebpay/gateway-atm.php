<?php

class RY_NewebPay_Gateway_Atm extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const Payment_Type = 'VACC';

    public function __construct()
    {
        $this->id = 'ry_newebpay_atm';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via ATM', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay ATM', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay ATM', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/atm.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 7);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_filter('ry_admin_payment_info-ry_newebpay_atm', [$this, 'show_payment_info'], 10, 2);

        parent::__construct();
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_ry_newebpay_atm_expire_date'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $_POST['woocommerce_ry_newebpay_atm_expire_date'] = intval($_POST['woocommerce_ry_newebpay_atm_expire_date']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($_POST['woocommerce_ry_newebpay_atm_expire_date'] < 1 || $_POST['woocommerce_ry_newebpay_atm_expire_date'] > 180) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['woocommerce_ry_newebpay_atm_expire_date'] = 3; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $_POST['woocommerce_ry_newebpay_atm_expire_date'] = 3; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        parent::process_admin_options();
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
