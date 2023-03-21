<?php

class RY_NewebPay_Shipping_CVS extends RY_WT_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'ry_newebpay_shipping_cvs';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('NewebPay shipping CVS', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/settings-newebpay-shipping-cvs.php';
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;

        $this->init();
    }

    public function is_available($package)
    {
        $is_available = false;

        list($MerchantID, $HashKey, $HashIV) = RY_NewebPay_Gateway::get_newebpay_api_info();
        if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
            $is_available = true;
        }

        if ($is_available) {
            $is_available = parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }
}
