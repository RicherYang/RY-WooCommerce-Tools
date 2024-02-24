<?php

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title'
    ],
    [
        'title' => __('Debug log', 'woocommerce'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'woocommerce') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log API / IPN information, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_smilepay_shipping') . '</code>'
            )
            . '<p class="description">' . __('Note: this may log personal information.', 'ry-woocommerce-tools') . '</p>'
    ],
    [
        'title' => __('Log status change', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_log_status_change',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Log status change at order notes.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto change order status', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_auto_order_status',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto change order status when get shipping status change.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto get shipping payment no', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_auto_get_no',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto get shipping payment no when order status is change to processing.', 'ry-woocommerce-tools')
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('Shipping note options', 'ry-woocommerce-tools'),
        'id' => 'note_options',
        'type' => 'title'
    ],
    [
        'title' => __('shipping item name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'shipping_item_name',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools'),
        'desc_tip' => true
    ],
    [
        'title' => __('Cvs shipping type', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools')
        ]
    ],
    [
        'id' => 'note_options',
        'type' => 'sectionend'
    ]
];
