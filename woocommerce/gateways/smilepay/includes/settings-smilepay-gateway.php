<?php
return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
    ],
    [
        'title' => __('Enable/Disable', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable SmilePay gateway method', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Debug log', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'woocommerce') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log SmilePay gateway events/message, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_smilepay_gateway') . '</code>'
            )
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-tools'),
        'id' => 'api_options',
        'type' => 'title'
    ],
    [
        'title' => __('SmilePay gateway sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_testmode',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Enable SmilePay gateway sandbox', 'ry-woocommerce-tools')
    ],
    [
        'title' => _x('Dcvc', 'SmilePay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_Dcvc',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => _x('Rvg2c', 'SmilePay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_Rvg2c',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => _x('Verify_key', 'SmilePay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_Verify_key',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => _x('Rot_check', 'SmilePay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_gateway_Rot_check',
        'type' => 'text',
        'default' => ''
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend'
    ]
];
