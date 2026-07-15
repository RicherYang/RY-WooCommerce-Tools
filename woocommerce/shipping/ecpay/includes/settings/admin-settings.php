<?php

defined('ABSPATH') or exit;

return [
    [
        'title' => __('Base options', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
    ],
    [
        'title' => __('Debug log', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'ry-woocommerce-tools') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log API / IPN information, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_shipping') . '</code>',
            )
            . '<p class="description">' . __('Note: this may log personal information.', 'ry-woocommerce-tools') . '</p>',
    ],
    [
        'title' => __('Log status change', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_log_status_change',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Log status change at order notes.', 'ry-woocommerce-tools'),
    ],
    [
        'title' => __('Auto change order status', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_auto_order_status',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto change order status when get shipping status change.', 'ry-woocommerce-tools'),
    ],
    [
        'title' => __('Auto get shipping note', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_auto_get_no',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto get shipping note when order status is change to processing.', 'ry-woocommerce-tools'),
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
        'title' => __('Trade no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[prefix]',
        'type' => 'text',
        'desc' => __('The prefix string of trade no. Only letters and numbers allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'autoload' => false,
    ],
    [
        'title' => __('shipping item name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[itemname]',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'autoload' => false,
    ],
    [
        'title' => __('Cvs shipping type', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools'),
        ],
    ],
    [
        'title' => __('Sender name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[name]',
        'type' => 'text',
        'desc_tip' => true,
        'autoload' => false,
    ],
    [
        'title' => __('Sender phone', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[phone]',
        'type' => 'text',
        'desc_tip' => true,
        'placeholder' => '(0x)xxxxxxx#xx',
        'custom_attributes' => [
            'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
        ],
        'autoload' => false,
    ],
    [
        'title' => __('Sender cellphone', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[cellphone]',
        'type' => 'text',
        'desc_tip' => true,
        'placeholder' => '09xxxxxxxx',
        'custom_attributes' => [
            'pattern' => '09\d{8}',
        ],
        'autoload' => false,
    ],
    [
        'title' => __('Sender zipcode ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[zipcode]',
        'type' => 'text',
        'autoload' => false,
    ],
    [
        'title' => __('Sender address ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[address]',
        'type' => 'text',
        'autoload' => false,
    ],
    [
        'title' => __('Shipping declare amount mode', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[declare_mode]',
        'type' => 'select',
        'default' => 'product',
        'options' => [
            'product' => __('product regular', 'ry-woocommerce-tools'),
            'payment' => __('payment amount', 'ry-woocommerce-tools'),
        ],
        'desc' => __('Only work with cash on delivery and can set different amount.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'autoload' => false,
    ],
    [
        'title' => __('Shipping declare amount over 20000', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[declare_over]',
        'type' => 'select',
        'default' => 'keep',
        'options' => [
            'keep' => __('keep amount', 'ry-woocommerce-tools'),
            'limit' => __('limit 2000', 'ry-woocommerce-tools'),
        ],
        'autoload' => false,
    ],
    [
        'title' => __('Shipping booking note print format', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[print]',
        'type' => 'select',
        'default' => '1',
        'options' => [
            '1' => _x('A4 Size', 'ecpay print format', 'ry-woocommerce-tools'),
            '2' => _x('A6 Size', 'ecpay print format', 'ry-woocommerce-tools'),
        ],
        'autoload' => false,
    ],
    [
        'id' => 'note_options',
        'type' => 'sectionend',
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-tools'),
        'id' => 'api_options',
        'type' => 'title',
    ],
    [
        'title' => __('Sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[testmode]',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable sandbox', 'ry-woocommerce-tools')
            . '<p class="description">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-tools') . '<p>',
        'autoload' => false,
    ],
    [
        'title' => _x('MerchantID', 'ECPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[MerchantID]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => _x('HashKey', 'ECPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[HashKey]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'title' => _x('HashIV', 'ECPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_apiinfo[HashIV]',
        'type' => 'text',
        'default' => '',
        'autoload' => false,
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
