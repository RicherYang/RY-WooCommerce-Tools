<?php

defined('ABSPATH') or exit;

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
        'desc' => '',
    ],
    [
        'title' => __('Debug log', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'ry-woocommerce-tools') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log API / IPN information, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_newebpay_gateway') . '</code>',
            )
            . '<p class="description">' . __('Note: this may log personal information.', 'ry-woocommerce-tools') . '</p>',
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[prefix]',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'autoload' => false,
    ],
    [
        'title' => __('payment item name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[item_name]',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'autoload' => false,
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
        'title' => __('Sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[testmode]',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable sandbox', 'ry-woocommerce-tools')
            . '<p class="description">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-tools') . '<p>',
        'autoload' => false,
    ],
    [
        'title' => _x('MerchantID', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[MerchantID]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => _x('HashKey', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[HashKey]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => _x('HashIV', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'newebpay_gateway_apiinfo[HashIV]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
