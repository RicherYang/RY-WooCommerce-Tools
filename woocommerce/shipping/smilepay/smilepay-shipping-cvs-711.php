<?php
class RY_SmilePay_Shipping_CVS_711 extends RY_SmilePay_Shipping_Base
{
    public static $Type = '7NET';

    public function __construct($instance_id = 0)
    {
        $this->id = 'ry_smilepay_shipping_cvs_711';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('SmilePay shipping CVS 711', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/includes/settings-smilepay-shipping-base.php');
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;

        $this->init();
    }
}
