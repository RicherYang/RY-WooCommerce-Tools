<?php

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
        'desc' => '',
    ],
    [
        'title' => __('Debug log', 'woocommerce'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'woocommerce') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log API / IPN information, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_newebpay_gateway') . '</code>',
            )
            . '<p class="description">' . __('Note: this may log personal information.', 'ry-woocommerce-tools') . '</p>',
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'title' => __('payment item name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'payment_item_name',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend',
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-tools'),
        'id' => 'api_options',
        'type' => 'title',
    ],
    [
        'title' => __('NewebPay gateway sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_testmode',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable NewebPay gateway sandbox', 'ry-woocommerce-tools')
            . '<p class="description">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-tools') . '<p>',
    ],
    [
        'title' => _x('MerchantID', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_MerchantID',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => _x('HashKey', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_HashKey',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => _x('HashIV', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_HashIV',
        'type' => 'text',
        'default' => '',
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
