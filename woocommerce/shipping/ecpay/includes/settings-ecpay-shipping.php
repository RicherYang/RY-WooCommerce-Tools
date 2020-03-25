<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title'
    ],
    [
        'title' => __('Enable/Disable', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_cvs',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay shipping method', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Debug log', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'woocommerce') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log ECPay shipping events/message, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_shipping') . '</code>'
            )
    ],
    [
        'title' => __('Log status change', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_log_status_change',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Log status change at order notes.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto get shipping payment no', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_auto_get_no',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto get shipping payment no when order status is change to processing.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Keep shipping phone', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_keep_shipping_phone',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Always show shipping phone field in checkout form.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto completed order', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_auto_completed',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto completed order when user getted products.', 'ry-woocommerce-tools')
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
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true
    ],
    [
        'title' => __('Cvs shipping type', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools')
        ]
    ],
    [
        'title' => __('Sender name', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_name',
        'type' => 'text',
        'desc' => __('Name length between 1 to 10 letter', 'ry-woocommerce-tools'),
        'desc_tip' => true
    ],
    [
        'title' => __('Sender phone', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_phone',
        'type' => 'text',
        'desc' => __('Phone format (0x)xxxxxxx#xx', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'placeholder' => '(0x)xxxxxxx#xx',
        'custom_attributes' => [
            'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
        ]
    ],
    [
        'title' => __('Sender cellphone', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_cellphone',
        'type' => 'text',
        'desc' => __('Cellphone format 09xxxxxxxx', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'placeholder' => '09xxxxxxxx',
        'custom_attributes' => [
            'pattern' => '09\d{8}',
        ]
    ],
    [
        'id' => 'note_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-tools'),
        'id' => 'api_options',
        'type' => 'title'
    ],
    [
        'title' => __('ECPay shipping sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_testmode',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Enable ECPay shipping sandbox', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('MerchantID', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_MerchantID',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashKey', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_HashKey',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashIV', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'ecpay_shipping_HashIV',
        'type' => 'text',
        'default' => ''
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend'
    ]
];
