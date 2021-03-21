<?php
class RY_ECPay_Shipping_Home_Tcat extends RY_ECPay_Shipping_Base
{
    public static $LogisticsType = 'Home';
    public static $LogisticsSubType = 'TCAT';

    public function __construct($instance_id = 0)
    {
        $this->id = 'ry_ecpay_shipping_home_tcat';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('ECPay shipping home Tcat', 'ry-woocommerce-tools');
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
        $this->instance_form_fields['cost']['default'] = 110;

        $this->init();
    }
}
