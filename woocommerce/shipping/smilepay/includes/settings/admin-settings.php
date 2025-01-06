<?php

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
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
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_smilepay_shipping') . '</code>',
            )
            . '<p class="description">' . __('Note: this may log personal information.', 'ry-woocommerce-tools') . '</p>',
    ],
    [
        'title' => __('Log status change', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_log_status_change',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Log status change at order notes.', 'ry-woocommerce-tools'),
    ],
    [
        'title' => __('Auto change order status', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_auto_order_status',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto change order status when get shipping status change.', 'ry-woocommerce-tools'),
    ],
    [
        'title' => __('Auto get shipping payment no', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_auto_get_no',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto get shipping payment no when order status is change to processing.', 'ry-woocommerce-tools'),
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend',
    ],
    [
        'title' => __('Shipping note options', 'ry-woocommerce-tools'),
        'id' => 'note_options',
        'type' => 'title',
    ],
    [
        'title' => __('shipping item name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'shipping_item_name',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'title' => __('Cvs shipping type', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools'),
        ],
    ],
    [
        'title' => __('Shipping box size ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_box_size',
        'type' => 'select',
        'default' => '1',
        'options' => [
            '0' => _x('By product', 'box size', 'ry-woocommerce-tools'),
            '1' => _x('60 cm', 'box size', 'ry-woocommerce-tools'),
            '2' => _x('90 cm', 'box size', 'ry-woocommerce-tools'),
            '3' => _x('120 cm', 'box size', 'ry-woocommerce-tools'),
            '4' => _x('150 cm', 'box size', 'ry-woocommerce-tools'),
        ],
        'desc' => __('By product is set box size to the biggest product size.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'title' => __('Shipping booking note print format ( TCAT )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_tcat_print_format',
        'type' => 'select',
        'default' => '2',
        'options' => [
            '2' => _x('two format', 'tcat print format', 'ry-woocommerce-tools'),
            '3' => _x('three format', 'tcat print format', 'ry-woocommerce-tools'),
        ],
    ],
    [
        'title' => __('Shipping delivery date ( TCAT )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'smilepay_shipping_tcat_delivery_date',
        'type' => 'select',
        'default' => '1',
        'options' => [
            '1' => _x('next day', 'tcat delivery date', 'ry-woocommerce-tools'),
            '2' => _x('after 2 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '3' => _x('after 3 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '4' => _x('after 4 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '5' => _x('after 5 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '6' => _x('after 6 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '7' => _x('after 7 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '8' => _x('after 8 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '9' => _x('after 9 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '10' => _x('after 10 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '11' => _x('after 11 days', 'tcat delivery date', 'ry-woocommerce-tools'),
            '12' => _x('after 12 days', 'tcat delivery date', 'ry-woocommerce-tools'),
        ],
        'desc' => __('The delivery date is sunday, change to monday.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'id' => 'note_options',
        'type' => 'sectionend',
    ],
];
