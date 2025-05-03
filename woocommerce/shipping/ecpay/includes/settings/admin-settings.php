<?php

use Automattic\WooCommerce\Utilities\I18nUtil;

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
        'title' => __('Auto get shipping payment no', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_auto_get_no',
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
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
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
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools'),
        ],
    ],
    [
        'title' => __('Sender name', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_sender_name',
        'type' => 'text',
        'desc' => __('Name length between 1 to 10 letter', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'title' => __('Sender phone', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_sender_phone',
        'type' => 'text',
        'desc' => __('Phone format (0x)xxxxxxx#xx', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'placeholder' => '(0x)xxxxxxx#xx',
        'custom_attributes' => [
            'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
        ],
    ],
    [
        'title' => __('Sender cellphone', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_sender_cellphone',
        'type' => 'text',
        'desc' => __('Cellphone format 09xxxxxxxx', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'placeholder' => '09xxxxxxxx',
        'custom_attributes' => [
            'pattern' => '09\d{8}',
        ],
    ],
    [
        'title' => __('Sender zipcode ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_sender_zipcode',
        'type' => 'text',
    ],
    [
        'title' => __('Sender address ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_sender_address',
        'type' => 'text',
    ],
    [
        'title' => __('Shipping declare amount over 20000', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_declare_over',
        'type' => 'select',
        'default' => 'keep',
        'options' => [
            'keep' => __('keep amount', 'ry-woocommerce-tools'),
            'limit' => __('limit 2000', 'ry-woocommerce-tools'),
        ],
        'desc' => __('Use product regular price as declare amount.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    [
        'title' => __('Shipping box size ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_box_size',
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
        'title' => sprintf(
            /* translators: %s: Weight unit */
            __('Product default weight (%s) ( Home delivery )', 'ry-woocommerce-tools'),
            I18nUtil::get_weight_unit_label(get_option('woocommerce_weight_unit')),
        ),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_product_weight',
        'default' => '0',
        'type' => 'text',
    ],
    /* 綠界取消支援
    [
        'title' => __('Pickup time ( Home delivery )', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_pickup_time',
        'type' => 'select',
        'default' => '4',
        'options' => [
            '1' => _x('morning', 'Pickup time', 'ry-woocommerce-tools'),
            '2' => _x('afternoon', 'Pickup time', 'ry-woocommerce-tools'),
            '4' => _x('unlimited', 'Pickup time', 'ry-woocommerce-tools'),
        ],
    ],
    */
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
        'title' => __('ECPay shipping sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_testmode',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay shipping sandbox', 'ry-woocommerce-tools')
            . '<p class="description">' . __('Note: Recommend using this for development purposes only.', 'ry-woocommerce-tools') . '<p>',
    ],
    [
        'title' => __('MerchantID', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_MerchantID',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => __('HashKey', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_HashKey',
        'type' => 'text',
        'default' => '',
    ],
    [
        'title' => __('HashIV', 'ry-woocommerce-tools'),
        'id' => RY_WT::OPTION_PREFIX . 'ecpay_shipping_HashIV',
        'type' => 'text',
        'default' => '',
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend',
    ],
];
