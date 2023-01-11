<?php

class RY_ECPay_Shipping_CVS_Ok extends RY_ECPay_Shipping_Base
{
    public static $LogisticsType = 'CVS';
    public static $LogisticsSubType = 'OKMART';

    public static $support_temp = ['1'];

    public function __construct($instance_id = 0)
    {
        $this->id = 'ry_ecpay_shipping_cvs_ok';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('ECPay shipping CVS Ok', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping-base.php');
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;
        $this->instance_form_fields['cost']['default'] = 55;

        $this->init();
    }
}
