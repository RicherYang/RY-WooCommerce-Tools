<?php
abstract class RY_Abstract_Payment_Gateway extends WC_Payment_Gateway
{
    protected $check_min_amount = 0;

    public function process_admin_options()
    {
        $filed_name = 'woocommerce_' . $this->id . '_min_amount';
        $_POST[$filed_name] = (int) $_POST[$filed_name];
        if ($_POST[$filed_name] < $this->check_min_amount) {
            $_POST[$filed_name] = $this->check_min_amount;
            WC_Admin_Settings::add_error(sprintf(__('%s minimum amount out of range. Set as default value.', 'ry-woocommerce-tools'), $this->method_title));
        }

        parent::process_admin_options();
    }
}
