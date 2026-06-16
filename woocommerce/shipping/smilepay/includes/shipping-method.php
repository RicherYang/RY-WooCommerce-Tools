<?php

defined('ABSPATH') or exit;

abstract class RY_WT_WC_SmilePay_Shipping_Method extends RY_WT_WC_Shipping_Method
{
    public function is_available($package)
    {
        $available = $this->is_enabled();

        if ($available) {
            $api_info = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();
            if (!empty($api_info['MerchantID']) && !empty($api_info['HashKey']) && !empty($api_info['HashIV'])) {
                $available = true;
            }
        }

        if ($available) {
            return parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }
}
