<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['expire_date'] = [
    'title' => __('Payment deadline', 'ry-woocommerce-tools'),
    'type' => 'number',
    'default' => 7,
    'placeholder' => 7,
    'description' => __('CVS allowable payment deadline from 1 day to 180 days.', 'ry-woocommerce-tools'),
    'custom_attributes' => [
        'min' => 1,
        'max' => 180,
        'step' => 1,
    ],
];

return $setting;
