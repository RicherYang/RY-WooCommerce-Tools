<?php

defined('ABSPATH') or exit;

class RY_SmilePay_Gateway_Cvs_711 extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const ID = 'ry_smilepay_cvs_711';

    public const PAYMENT_TYPE = '4';

    protected int $check_min_amount = 35;

    protected int $check_max_amount = 20000;

    protected array $check_expire_date = [120, 10080];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS 7-11', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay CVS 7-11', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay CVS 7-11', 'ry-woocommerce-tools');
        $this->get_code_mode = true;

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/cvs.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 4320);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('CVS code', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_cvs_PaymentNo')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_cvs_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
