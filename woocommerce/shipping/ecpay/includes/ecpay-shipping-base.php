<?php
abstract class RY_ECPay_Shipping_Base extends RY_WT_Shipping_Method
{
    public function is_available($package)
    {
        $is_available = false;

        list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Shipping::get_ecpay_api_info();
        if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
            $is_available = true;
        }

        if ($is_available) {
            foreach ($package['contents'] as $item_id => $values) {
                $temp = $values['data']->get_meta('_ry_shipping_temp', true);
                if (empty($temp) && $values['data']->get_type() == 'variation') {
                    $parent_product = wc_get_product($values['data']->get_parent_id());
                    $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                }
                $temp = empty($temp) ? '1' : $temp;
                if (!in_array($temp, static::$support_temp)) {
                    $is_available = false;
                    break;
                }
            }
        }

        if ($is_available) {
            $is_available = parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }
}
