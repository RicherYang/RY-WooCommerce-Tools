<?php
class RY_SmilePay_Shipping_Base extends RY_Shipping_Method
{
    public function is_available($package)
    {
        $is_available = false;

        list($MerchantID, $HashKey, $HashIV) = RY_SmilePay_Gateway::get_smilepay_api_info();
        if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
            $is_available = true;
        }

        if ($is_available) {
            $is_available = parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }
}
