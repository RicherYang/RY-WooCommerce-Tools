<?php

class RY_NewebPay_Gateway_Barcode extends RY_WT_WC_NewebPay_Payment_Gateway
{
    public const Payment_Type = 'BARCODE';

    protected $check_min_amount = 20;

    protected $check_max_amount = 40000;

    public function __construct()
    {
        $this->id = 'ry_newebpay_barcode';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via BARCODE', 'ry-woocommerce-tools');
        $this->method_title = __('NewebPay BARCODE', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via NewebPay BARCODE', 'ry-woocommerce-tools');

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/barcode.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 7);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_filter('ry_admin_payment_info-ry_newebpay_barcode', [$this, 'show_payment_info'], 10, 2);

        parent::__construct();
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_ry_newebpay_barcode_expire_date'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $_POST['woocommerce_ry_newebpay_barcode_expire_date'] = intval($_POST['woocommerce_ry_newebpay_barcode_expire_date']); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($_POST['woocommerce_ry_newebpay_barcode_expire_date'] < 1 || $_POST['woocommerce_ry_newebpay_barcode_expire_date'] > 180) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['woocommerce_ry_newebpay_barcode_expire_date'] = 7; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $_POST['woocommerce_ry_newebpay_barcode_expire_date'] = 7; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        parent::process_admin_options();
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('Barcode 1', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_barcode_Barcode1')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 2', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_barcode_Barcode2')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 3', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_barcode_Barcode3')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_newebpay_barcode_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
