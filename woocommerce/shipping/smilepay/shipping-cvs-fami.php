<?php

class RY_SmilePay_Shipping_CVS_Fami extends RY_WT_WC_SmilePay_Shipping_Method
{
    public const Shipping_Type = 'FAMI';
    public const Support_Temp = ['1'];

    public function __construct($instance_ID = 0)
    {
        $this->id = 'ry_smilepay_shipping_cvs_fami';
        $this->instance_id = absint($instance_ID);
        $this->method_title = __('SmilePay shipping CVS Fami', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/settings/cvs.php';
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;

        $this->init();
    }
}
