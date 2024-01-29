<?php

class RY_ECPay_Shipping_CVS_Family extends RY_WT_WC_ECPay_Shipping_Method
{
    public const Shipping_Type = 'CVS';
    public const Shipping_Sub_Type = 'FAMI';

    public function __construct($instance_ID = 0)
    {
        $this->id = 'ry_ecpay_shipping_cvs_family';
        $this->instance_id = absint($instance_ID);
        $this->method_title = __('ECPay shipping CVS Family', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings/cvs.php';
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;
        $this->instance_form_fields['cost']['default'] = 65;

        $this->init();
    }
}
