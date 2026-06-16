<?php

defined('ABSPATH') or exit;

abstract class RY_WT_WC_ECPay_Shipping_Method extends RY_WT_WC_Shipping_Method
{
    public function is_available($package)
    {
        $available = $this->is_enabled();

        if ($available) {
            $api_info = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();
            if (!empty($api_info['MerchantID']) && !empty($api_info['HashKey']) && !empty($api_info['HashIV'])) {
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
        $rate['meta_data']['LogisticsType'] = get_called_class()::SHIPPING_TYPE;
        $rate['meta_data']['LogisticsSubType'] = get_called_class()::Shipping_Sub_Type;
        if ('CVS' == $rate['meta_data']['LogisticsType']) {
            $api_info = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();
            if ('C2C' === RY_WT::get_option('ecpay_shipping_cvs_type', 'C2C')) {
                $rate['meta_data']['LogisticsSubType'] .= 'C2C';
            }
        }

        $cvs_info = (array) WC()->session->get('ry_ecpay_cvs_info', []);
        if (isset($cvs_info['LogisticsSubType']) && str_starts_with($cvs_info['LogisticsSubType'], $rate['meta_data']['LogisticsSubType'])) {
            $rate['meta_data']['RYCvsInfo'] = $cvs_info;
        }

        return $rate;
    }
}
