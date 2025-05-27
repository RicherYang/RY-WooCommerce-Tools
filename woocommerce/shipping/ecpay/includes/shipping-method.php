<?php

abstract class RY_WT_WC_ECPay_Shipping_Method extends RY_WT_WC_Shipping_Method
{
    public function is_available($package)
    {
        $available = $this->is_enabled();

        if ($available) {
            list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();
            if (!empty($MerchantID) && !empty($HashKey) && !empty($HashIV)) {
                $available = true;
            }
        }

        if ($available) {
            return parent::is_available($package);
        }

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }

    protected function add_rate_meta_data($rate)
    {
        $rate['meta_data']['LogisticsType'] = get_called_class()::Shipping_Type;
        $rate['meta_data']['LogisticsSubType'] = get_called_class()::Shipping_Sub_Type;
        if ('CVS' == $rate['meta_data']['LogisticsType']) {
            list($MerchantID, $HashKey, $HashIV, $cvs_type) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();
            if ('C2C' === $cvs_type) {
                $rate['meta_data']['LogisticsSubType'] .= 'C2C';
            }
        }

        $cvs_info = (array) WC()->session->get('ry_ecpay_cvs_info', []);
        if (($cvs_info['shipping_methods'] ?? '') === WC()->session->get('chosen_shipping_methods')) {
            $rate['meta_data']['LogisticsInfo'] = $cvs_info;
        }

        return $rate;
    }
}
