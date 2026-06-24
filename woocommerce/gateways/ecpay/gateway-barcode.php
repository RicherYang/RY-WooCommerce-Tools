<?php

defined('ABSPATH') or exit;

class RY_ECPay_Gateway_Barcode extends RY_WT_WC_ECPay_Payment_Gateway
{
    public const ID = 'ry_ecpay_barcode';

    public const PAYMENT_TYPE = 'BARCODE';

    protected int $check_min_amount = 16;

    protected int $check_max_amount = 20000;

    protected array $check_expire_date = [1, 30];

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via BARCODE', 'ry-woocommerce-tools');
        $this->method_title = __('ECPay BARCODE', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/barcode.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 7);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('Barcode 1', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_barcode_Barcode1')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 2', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_barcode_Barcode2')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 3', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_barcode_Barcode3')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_ecpay_barcode_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
