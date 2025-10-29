<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['applepay'] = [
    'title' => __('Apple Pay', 'ry-woocommerce-tools'),
    'label' => __('Support Apple Pay', 'ry-woocommerce-tools'),
    'type' => 'checkbox',
    'default' => 'yes',
];

return $setting;
