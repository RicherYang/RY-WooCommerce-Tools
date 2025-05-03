<?php

return [
    'enabled' => [
        'title' => __('Enable/Disable', 'ry-woocommerce-tools'),
        'label' => sprintf(
            /* translators: %s: Gateway method title */
            __('Enable %s', 'ry-woocommerce-tools'),
            $this->method_title,
        ),
        'type' => 'checkbox',
        'default' => 'no',
    ],
    'title' => [
        'title' => __('Title', 'ry-woocommerce-tools'),
        'type' => 'text',
        'default' => $this->method_title,
        'placeholder' => $this->method_title,
        'description' => __('This controls the title which the user sees during checkout.', 'ry-woocommerce-tools'),
        'desc_tip' => true,
    ],
    'description' => [
        'title' => __('Description', 'ry-woocommerce-tools'),
        'type' => 'text',
        'default' => $this->order_button_text,
        'placeholder' => $this->order_button_text,
        'desc_tip' => true,
        'description' => __('This controls the description which the user sees during checkout.', 'ry-woocommerce-tools'),
    ],
    'min_amount' => [
        'title' => __('Minimum order amount', 'ry-woocommerce-tools'),
        'type' => 'number',
        'default' => 0,
        'placeholder' => 0,
        'description' => __('0 to disable minimum amount limit.', 'ry-woocommerce-tools'),
        'custom_attributes' => [
            'min' => 0,
            'step' => 1,
        ],
    ],
    'max_amount' => [
        'title' => __('Maximum order amount', 'ry-woocommerce-tools'),
        'type' => 'number',
        'default' => 0,
        'placeholder' => 0,
        'description' => __('0 to disable maximum amount limit.', 'ry-woocommerce-tools'),
        'custom_attributes' => [
            'min' => 0,
            'step' => 1,
        ],
    ],
];
