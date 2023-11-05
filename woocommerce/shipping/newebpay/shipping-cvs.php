<?php

class RY_NewebPay_Shipping_CVS extends RY_WT_WC_NewebPay_Shipping_Method
{
    public const Support_Temp = ['1'];

    public function __construct($instance_ID = 0)
    {
        $this->id = 'ry_newebpay_shipping_cvs';
        $this->instance_id = absint($instance_ID);
        $this->method_title = __('NewebPay shipping CVS', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/settings/cvs.php';
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;

        $this->init();
    }

    public function is_available($package)
    {
        $is_available = false;

        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();
        if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
            $is_available = true;
        }

        if ($is_available) {
            $is_available = parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }
}
