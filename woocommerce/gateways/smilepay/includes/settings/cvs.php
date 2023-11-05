<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['expire_date'] = [
    'title' => __('Payment deadline (minutes)', 'ry-woocommerce-tools'),
    'type' => 'number',
    'default' => 4320,
    'placeholder' => 4320,
    'description' => __('CVS allowable payment deadline from 2 hours to 7 days.', 'ry-woocommerce-tools'),
    'custom_attributes' => [
        'min' => 1,
        'max' => 43200,
        'step' => 1
    ]
];

return $setting;
