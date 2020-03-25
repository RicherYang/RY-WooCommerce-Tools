<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
        'desc' => __('Because NewebPay limit, the shipping note no or shipping status can not show in site admin.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Enable/Disable', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'newebpay_shipping',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable NewebPay shipping method', 'ry-woocommerce-tools')
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ]
];
