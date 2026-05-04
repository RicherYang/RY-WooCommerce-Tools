<?php

defined('ABSPATH') or exit;

$settings = [
    'title' => [
        'title' => __('Title', 'ry-woocommerce-tools'),
        'type' => 'text',
        'default' => $this->method_title,
        'description' => __('This controls the title which the user sees during checkout.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    'tax_status' => [
        'title' => __('Tax status', 'ry-woocommerce-tools'),
        'type' => 'select',
        'default' => 'none',
        'options' => [
            'taxable' => __('Taxable', 'ry-woocommerce-tools'),
            'none' => _x('None', 'Tax status', 'ry-woocommerce-tools'),
        ],
        'class' => 'wc-enhanced-select',
    ],
    'cost' => [
        'title' => __('Shipping cost', 'ry-woocommerce-tools'),
        'type' => 'text',
        'default' => '0',
        'placeholder' => '',
        'description' => sprintf(
            /* translators: %s: advanced costs setting url */
            __('Support cost formula. Learn more about %s', 'ry-woocommerce-tools'),
            ' <a href="https://ry-plugin.com/blog/setting-shipping-cost" target="_blank">' . __('advanced costs', 'ry-woocommerce-tools') . '</a>'
        ),
        'class' => 'wc-shipping-modal-price',
        'sanitize_callback' => [$this, 'sanitize_cost'],
    ],
    'cost_requires' => [
        'title' => __('Free shipping requires...', 'ry-woocommerce-tools'),
        'type' => 'select',
        'default' => '',
        'options' => [
            '' => __('N/A', 'ry-woocommerce-tools'),
            'coupon' => __('A valid free shipping coupon', 'ry-woocommerce-tools'),
            'min_amount' => __('A minimum order amount', 'ry-woocommerce-tools'),
            'min_amount_or_coupon' => __('A minimum order amount OR a coupon', 'ry-woocommerce-tools'),
            'min_amount_and_coupon' => __('A minimum order amount AND a coupon', 'ry-woocommerce-tools'),
        ],
        'class' => 'wc-enhanced-select ry-shipping-cost_requires',
    ],
    'min_amount' => [
        'title' => __('Minimum order amount', 'ry-woocommerce-tools'),
        'type' => 'price',
        'default' => 0,
        'placeholder' => wc_format_localized_price(0),
        'description' => __('Users will need to spend this amount to get free shipping (if enabled above).', 'ry-woocommerce-tools'),
        'desc_tip' => true,
        'class' => 'wc-shipping-modal-price ry-shipping-min_amount',
    ],
];

$shipping_classes = WC()->shipping->get_shipping_classes();

if (!empty($shipping_classes)) {
    $settings['class_available'] = [
        'title' => __('Shipping available', 'ry-woocommerce-tools'),
        'type' => 'title',
        'default' => '',
        'description' => sprintf(
            /* translators: %s: shipping class setting url */
            __('These shipping available based on the <a href="%s">product shipping class</a>.', 'ry-woocommerce-tools'),
            esc_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=classes')),
        ),
    ];
    foreach ($shipping_classes as $shipping_class) {
        if (!isset($shipping_class->term_id)) {
            continue;
        }
        $settings['class_available_' . $shipping_class->term_id] = [
            'title' => sprintf(
                /* translators: %s: shipping class name */
                __('"%s" available', 'ry-woocommerce-tools'),
                esc_html($shipping_class->name),
            ),
            'type' => 'checkbox',
            'default' => $this->get_option('class_available_' . $shipping_class->term_id, 'yes'),
        ];
    }
}

return $settings;
