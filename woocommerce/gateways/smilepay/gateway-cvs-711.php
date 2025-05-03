<?php

class RY_SmilePay_Gateway_Cvs_711 extends RY_WT_WC_SmilePay_Payment_Gateway
{
    public const Payment_Type = 4;

    protected $check_min_amount = 35;

    protected $check_max_amount = 20000;

    public function __construct()
    {
        $this->id = 'ry_smilepay_cvs_711';
        $this->has_fields = false;
        $this->order_button_text = __('Pay via CVS 7-11', 'ry-woocommerce-tools');
        $this->method_title = __('SmilePay CVS 7-11', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->process_payment_note = __('Pay via SmilePay CVS 7-11', 'ry-woocommerce-tools');
        $this->get_code_mode = true;

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/cvs.php';
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->expire_date = (int) $this->get_option('expire_date', 4320);
        $this->min_amount = (int) $this->get_option('min_amount', 0);
        $this->max_amount = (int) $this->get_option('max_amount', 0);

        add_filter('ry_admin_payment_info-ry_smilepay_cvs_711', [$this, 'show_payment_info'], 10, 2);

        parent::__construct();
    }

    public function process_admin_options()
    {
        $this->set_post_data(wp_unslash($_POST)); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (isset($this->data['woocommerce_ry_smilepay_cvs_711_expire_date'])) {
            $this->data['woocommerce_ry_smilepay_cvs_711_expire_date'] = (int) $this->data['woocommerce_ry_smilepay_cvs_711_expire_date'];
            if ($this->data['woocommerce_ry_smilepay_cvs_711_expire_date'] < 120 || $this->data['woocommerce_ry_smilepay_cvs_711_expire_date'] > 10080) {
                $this->data['woocommerce_ry_smilepay_cvs_711_expire_date'] = 4320;
                WC_Admin_Settings::add_error(__('Payment expire date out of range. Set as default value.', 'ry-woocommerce-tools'));
            }
        } else {
            $this->data['woocommerce_ry_smilepay_cvs_711_expire_date'] = 4320;
        }

        parent::process_admin_options();
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
