<?php

class RY_NewebPay_Gateway_Cvs extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const Payment_Type = 'CVS';

    protected $check_min_amount = 30;

    protected $check_max_amount = 20000;

    public function __construct()
    {
        $this->id = 'ry_newebpay_cvs';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay CVS', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay CVS', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/cvs.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 7);

        add_filter('ry_admin_payment_info-ry_newebpay_cvs', [$this, 'show_payment_info'], 10, 2);
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_ry_newebpay_cvs_expire_date'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $_POST['woocommerce_ry_newebpay_cvs_expire_date'] = intval($_POST['woocommerce_ry_newebpay_cvs_expire_date']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($_POST['woocommerce_ry_newebpay_cvs_expire_date'] < 1 || $_POST['woocommerce_ry_newebpay_cvs_expire_date'] > 180) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['woocommerce_ry_newebpay_cvs_expire_date'] = 7; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $_POST['woocommerce_ry_newebpay_cvs_expire_date'] = 7; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        parent::process_admin_options();
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('CVS code', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_cvs_PaymentNo')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_cvs_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
