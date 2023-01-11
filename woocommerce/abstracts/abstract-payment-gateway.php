<?php

abstract class RY_Abstract_Payment_Gateway extends WC_Payment_Gateway
{
    protected $check_min_amount = 0;

    public function process_admin_options()
    {
        $filed_name = 'woocommerce_' . $this->id . '_min_amount';
        if (isset($_POST[$filed_name])) {
            $_POST[$filed_name] = (int) $_POST[$filed_name];
            if ($_POST[$filed_name] < $this->check_min_amount) {
                $_POST[$filed_name] = $this->check_min_amount;
                WC_Admin_Settings::add_error(sprintf(
                    /* translators: %s: minimum amount */
                    __('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'),
                    $this->method_title
                ));
            }
        } else {
            $_POST[$filed_name] = $this->check_min_amount;
        }

        if (isset($this->check_max_amount)) {
            $filed_name = 'woocommerce_' . $this->id . '_max_amount';
            if (isset($_POST[$filed_name])) {
                $_POST[$filed_name] = (int) $_POST[$filed_name];
                if ($_POST[$filed_name] > $this->check_max_amount) {
                    WC_Admin_Settings::add_message(sprintf(
                        /* translators: %1$s: Gateway method title, %2$d normal maximum */
                        __('%1$s maximum amount more then normal maximum (%2$d).', 'ry-woocommerce-tools'),
                        $this->method_title,
                        $this->check_max_amount
                    ));
                }
            }
        }

        parent::process_admin_options();
    }
}
