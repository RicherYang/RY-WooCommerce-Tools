<?php

defined('ABSPATH') or exit;

abstract class RY_WT_WC_Payment_Gateway extends WC_Payment_Gateway
{
    public const SUPPORT_REFUND = false;

    public int $min_amount = 0;

    public int $expire_date = 0;

    protected int $check_min_amount = 0;

    protected int $check_max_amount = 0;

    protected array $check_expire_date = [];

    public function __construct()
    {
        $this->init_settings();

        $this->title = $this->settings['title'] ?? '';
        if (empty($this->title)) {
            $this->title = $this->method_title;
        }
        $this->description = $this->settings['description'] ?? '';
        $this->min_amount = (int) ($this->settings['min_amount'] ?? 0);
        $this->max_amount = (int) ($this->settings['max_amount'] ?? 0);

        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        if ('yes' === $this->enabled) {
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() !== $this->id) {
            return;
        }

        $payment_info = apply_filters('ry_admin_payment_info-' . $order->get_payment_method(), '', $order);
        $payment_info = apply_filters('ry_admin_payment_info', $payment_info, $order);

        if (!empty($payment_info)) {
            echo '<h3 style="clear:both">' . esc_html__('Payment details', 'ry-woocommerce-tools') . '</h3><table>';
            echo wp_kses($payment_info, [
                'table' => [],
                'tr' => [],
                'td' => [],
                'button' => [
                    'id' => true,
                    'type' => true,
                    'class' => true,
                    'data-orderid' => true,
                ],
            ]);
            echo '</table>';
        }
    }

    public function process_admin_options()
    {
        $post_data = $this->get_post_data();

        $filed_name = 'woocommerce_' . $this->id . '_min_amount';
        if (isset($post_data[$filed_name])) {
            $post_data[$filed_name] = intval($post_data[$filed_name]);
            if ($this->check_min_amount > 0 && $post_data[$filed_name] < $this->check_min_amount) {
                WC_Admin_Settings::add_message(sprintf(
                    /* translators: %1$s: Gateway method title, %2$d normal minimum */
                    __('%1$s minimum amount less then normal minimum (%2$d).', 'ry-woocommerce-tools'),
                    $this->method_title,
                    $this->check_min_amount,
                ));
            }
        }

        $filed_name = 'woocommerce_' . $this->id . '_max_amount';
        if (isset($post_data[$filed_name])) {
            $post_data[$filed_name] = intval($post_data[$filed_name]);
            if ($this->check_max_amount > 0 && $post_data[$filed_name] > $this->check_max_amount) {
                WC_Admin_Settings::add_message(sprintf(
                    /* translators: %1$s: Gateway method title, %2$d normal maximum */
                    __('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'),
                    $this->method_title,
                    $this->check_max_amount,
                ));
            }
        }

        $filed_name = 'woocommerce_' . $this->id . '_expire_date';
        if (isset($post_data[$filed_name]) && count($this->check_expire_date) === 2) {
            $post_data[$filed_name] = intval($post_data[$filed_name]);
            if ($post_data[$filed_name] < $this->check_expire_date[0]) {
                WC_Admin_Settings::add_message(sprintf(
                    /* translators: %1$s: Gateway method title, %2$d normal minimum */
                    __('%1$s expire date less then normal minimum (%2$d).', 'ry-woocommerce-tools'),
                    $this->method_title,
                    $this->check_expire_date[0],
                ));
            }
            if ($post_data[$filed_name] > $this->check_expire_date[1]) {
                WC_Admin_Settings::add_message(sprintf(
                    /* translators: %1$s: Gateway method title, %2$d normal maximum */
                    __('%1$s expire date more then normal maximum (%2$d).', 'ry-woocommerce-tools'),
                    $this->method_title,
                    $this->check_expire_date[1],
                ));
            }
        }

        $this->set_post_data($post_data);

        parent::process_admin_options();
    }

    public function is_available()
    {
        $is_available = ('yes' === $this->enabled);

        if ($is_available && WC()->cart) {
            $total = $this->get_order_total();
            if (0 < $total) {
                if ($this->min_amount > 0 && $total <= $this->min_amount) {
                    $is_available = false;
                }
                if ($this->max_amount > 0 && $total >= $this->max_amount) {
                    $is_available = false;
                }
            }
        }

        return $is_available;
    }

    public function process_payment($order_ID)
    {
        $order = wc_get_order($order_ID);

        wc_maybe_reduce_stock_levels($order_ID);
        wc_release_stock_for_order($order);

        return [
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
