<?php

abstract class RY_WT_WC_Payment_Gateway extends WC_Payment_Gateway
{
    public $min_amount = 0;

    public $expire_date = 0;

    protected $check_min_amount = 0;

    protected $check_max_amount = 0;

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
        if ($this->check_min_amount > 0) {
            $filed_name = 'woocommerce_' . $this->id . '_min_amount';
            if (isset($_POST[$filed_name])) {
                $_POST[$filed_name] = (int) $_POST[$filed_name];
                if ($_POST[$filed_name] < $this->check_min_amount) {
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
            if (isset($_POST[$filed_name])) {
                $_POST[$filed_name] = (int) $_POST[$filed_name];
                if ($_POST[$filed_name] > $this->check_max_amount) {
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
