<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['expire_date'] = [
    'title' => __('Payment deadline (minutes)', 'ry-woocommerce-tools'),
    'type' => 'number',
    'default' => 10080,
    'placeholder' => 10080,
    'description' => __('CVS allowable payment deadline from 1 minute to 7 days.', 'ry-woocommerce-tools'),
    'custom_attributes' => [
        'min' => 1,
        'max' => 10080,
        'step' => 1
    ]
];

return $setting;
