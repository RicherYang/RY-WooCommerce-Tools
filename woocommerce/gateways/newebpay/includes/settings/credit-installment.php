<?php

$setting = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/global-setting.php';

$setting['number_of_periods'] = [
    'title' => __('Enable number of periods', 'ry-woocommerce-tools'),
    'type' => 'multiselect',
    'class' => 'wc-enhanced-select',
    'css' => 'width: 400px;',
    'default' => '',
    'description' => '',
    'options' => [
        /* translators: %d number of periods */
        3 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 3),
        /* translators: %d number of periods */
        6 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 6),
        /* translators: %d number of periods */
        12 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 12),
        /* translators: %d number of periods */
        18 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 18),
        /* translators: %d number of periods */
        24 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 24),
        /* translators: %d number of periods */
        30 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 30),
    ],
    'desc_tip' => true,
    'custom_attributes' => [
        'data-placeholder' => _x('Number of periods', 'Gateway setting', 'ry-woocommerce-tools'),
    ],
];

return $setting;
