<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['expire_date'] = [
    'title' => __('Payment deadline', 'ry-woocommerce-tools'),
    'type' => 'number',
    'default' => 3,
    'placeholder' => 3,
    'description' => __('ATM allowable payment deadline from 1 day to 60 days.', 'ry-woocommerce-tools'),
    'custom_attributes' => [
        'min' => 1,
        'max' => 60,
        'step' => 1,
    ],
];

return $setting;
