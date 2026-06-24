<?php

defined('ABSPATH') or exit;

class RY_ECPay_Gateway_Cvs extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const ID = 'ry_ecpay_cvs';

    public const PAYMENT_TYPE = 'CVS';

    protected int $check_min_amount = 31;

    protected int $check_max_amount = 6000;

    protected array $check_expire_date = [1, 43200];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay CVS', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/cvs.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 10080);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('CVS code', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_cvs_PaymentNo')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_cvs_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
