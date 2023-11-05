<?php

abstract class RY_WT_WC_SmilePay_Shipping_Method extends RY_WT_WC_Shipping_Method
{
    public function is_available($package)
    {
        $available = $this->is_enabled();

        if($available) {
            list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();
            if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
                $is_available = true;
            }
        }

        if ($available) {
            return parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }
}
