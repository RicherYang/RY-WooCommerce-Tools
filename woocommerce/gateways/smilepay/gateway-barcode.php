<?php

defined('ABSPATH') or exit;

class RY_SmilePay_Gateway_Barcode extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const ID = 'ry_smilepay_barcode';

    public const PAYMENT_TYPE = '3';

    protected int $check_min_amount = 25;

    protected int $check_max_amount = 20000;

    public function __construct()
    {
        $this->id = self::ID;
        $this->has_fields = false;
        $this->order_button_text = __('Pay via BARCODE', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay BARCODE', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay BARCODE', 'ry-woocommerce-tools');
        $this->get_code_mode = true;

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/barcode.php';

        parent::__construct();

        $this->expire_date = (int) ($this->settings['expire_date'] ?? 7);

        add_filter('ry_admin_payment_info-' . $this->id, [$this, 'show_payment_info'], 10, 2);
    }

    public function process_admin_options()
    {
        $post_data = $this->get_post_data();

        $filed_name = 'woocommerce_' . $this->id . '_expire_date';
        if (isset($post_data[$filed_name])) {
            $post_data[$filed_name] = intval($post_data[$filed_name]);
            if ($post_data[$filed_name] < 1 || $post_data[$filed_name] > 30) {
                $post_data[$filed_name] = 7;
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $post_data[$filed_name] = 7;
        }

        $this->set_post_data($post_data);

        parent::process_admin_options();
    }

    public function show_payment_info($html, $order)
    {
        $html .= '<tr>
            <td>' . esc_html__('Barcode 1', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_barcode_Barcode1')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 2', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_barcode_Barcode2')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Barcode 3', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_barcode_Barcode3')) . '</td>
        </tr>';
        $html .= '<tr>
            <td>' . esc_html__('Payment deadline', 'ry-woocommerce-tools') . '</td>
            <td>' . esc_html($order->get_meta('_smilepay_barcode_ExpireDate')) . '</td>
        </tr>';
        return $html;
    }
}
