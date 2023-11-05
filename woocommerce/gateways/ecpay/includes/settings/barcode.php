<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['expire_date'] = [
    'title' => __('Payment deadline', 'ry-woocommerce-tools'),
    'type' => 'number',
    'default' => 7,
    'placeholder' => 7,
    'description' => __('Barcode allowable payment deadline from 1 day to 30 days.', 'ry-woocommerce-tools'),
    'custom_attributes' => [
        'min' => 1,
        'max' => 30,
        'step' => 1
    ]
];

return $setting;
