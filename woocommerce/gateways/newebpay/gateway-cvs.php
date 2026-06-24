<?php

defined('ABSPATH') or exit;

class RY_NewebPay_Gateway_Cvs extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const ID = 'ry_newebpay_cvs';

    public const PAYMENT_TYPE = 'CVS';

    public const INFO_TEMPLATE = 'order-newebpay-payment-info-cvs.php';

    protected int $check_min_amount = 30;

    protected int $check_max_amount = 20000;

    protected array $check_expire_date = [1, 180];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay CVS', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/cvs.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 7);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
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
