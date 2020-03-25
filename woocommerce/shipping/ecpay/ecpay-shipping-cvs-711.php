<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_ECPay_Shipping_CVS_711 extends RY_ECPay_Shipping_CVS
{
    public static $LogisticsType = 'CVS';
    public static $LogisticsSubType = 'UNIMART';

    public function __construct($instance_id = 0)
    {
        $this->id = 'ry_ecpay_shipping_cvs_711';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('ECPay shipping CVS 7-11', 'ry-woocommerce-tools');
        $this->method_description = '';
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        if (empty($this->instance_form_fields)) {
            $this->instance_form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping-cvs.php');
        }
        $this->instance_form_fields['title']['default'] = $this->method_title;
        $this->instance_form_fields['cost']['default'] = 65;

        $this->init();
    }
}
