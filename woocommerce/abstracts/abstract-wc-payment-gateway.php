<?php

abstract class RY_WT_WC_Payment_Gateway extends WC_Payment_Gateway
{
    public $min_amount = 0;

    public $expire_date = 0;

    protected $check_min_amount = 0;

    protected $check_max_amount = 0;

    public function __construct()
    {
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'admin_payment_info']);
    }

    public function admin_payment_info($order)
    {
        if ($order->get_payment_method() != $this->id) {
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

    public function is_available()
    {
        $is_available = ('yes' === $this->enabled);

        if (WC()->cart) {
            $total = $this->get_order_total();
            if (0 < $total) {
                if ($this->min_amount > 0 && $total < $this->min_amount) {
                    $is_available = false;
                }
                if ($this->max_amount > 0 && $total > $this->max_amount) {
                    $is_available = false;
                }
            }
        }

        return $is_available;
    }

    public function process_admin_options()
    {
        if (empty($this->data) || !is_array($this->data)) {
            $this->set_post_data(wp_unslash($_POST)); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        if ($this->check_min_amount > 0) {
            $filed_name = 'woocommerce_' . $this->id . '_min_amount';
            if (isset($this->data[$filed_name])) {
                $this->data[$filed_name] = (int) $this->data[$filed_name];
                if ($this->data[$filed_name] < $this->check_min_amount) {
                    WC_Admin_Settings::add_error(sprintf(
                        /* translators: %1$s: Gateway method title, %2$d normal minimum */
                        __('%1$s minimum amount less then normal minimum (%2$d).', 'ry-woocommerce-tools'),
                        $this->method_title,
                        $this->check_min_amount,
                    ));
                }
            }
        }

        if ($this->check_max_amount > 0) {
            $filed_name = 'woocommerce_' . $this->id . '_max_amount';
            if (isset($this->data[$filed_name])) {
                $this->data[$filed_name] = (int) $this->data[$filed_name];
                if ($this->data[$filed_name] > $this->check_max_amount) {
                    WC_Admin_Settings::add_error(sprintf(
                        /* translators: %1$s: Gateway method title, %2$d normal maximum */
                        __('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'),
                        $this->method_title,
                        $this->check_max_amount,
                    ));
                }
            }
        }

        parent::process_admin_options();
    }
}
