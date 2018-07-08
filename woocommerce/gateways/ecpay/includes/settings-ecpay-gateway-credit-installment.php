<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

return array(
	'enabled' => array(
		'title' => __('Enable/Disable', 'woocommerce'),
		'label' => sprintf(__('Enable %s', 'ry-woocommerce-tools'), $this->method_title),
		'type' => 'checkbox',
		'default' => 'no',
	),
	'inpay' => array(
		'title' => __('Inpay', 'ry-woocommerce-tools'),
		'label' => sprintf(__('Enable inpay', 'ry-woocommerce-tools'), $this->method_title),
		'type' => 'checkbox',
		'default' => 'no',
	),
	'title' => array(
		'title' => __('Title', 'woocommerce'),
		'type' => 'text',
		'default' => $this->method_title,
		'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
		'desc_tip' => true,
	),
	'description' => array(
		'title' => __( 'Description', 'woocommerce' ),
		'type' => 'text',
		'default' => $this->order_button_text,
		'desc_tip' => true,
		'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
	),
	'min_amount' => array(
		'title' => __('Minimum order amount', 'ry-woocommerce-tools'),
		'type' => 'number',
		'default' => 0,
		'placeholder' => 0,
		'description' => __('0 to disable minimum amount limit.', 'ry-woocommerce-tools'),
		'custom_attributes' => array(
			'min' => 0,
			'step' => 1
		)
	),
	'number_of_periods' => array(
		'title' => __('Enable number of periods', 'ry-woocommerce-tools'),
		'type' => 'multiselect',
		'class' => 'wc-enhanced-select',
		'css' => 'width: 400px;',
		'default' => '',
		'description' => '',
		'options' => array(
			3 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 3),
			6 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 6),
			12 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 12),
			18 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 18),
			24 => sprintf(__('%d periods', 'ry-woocommerce-tools'), 24),
		),
		'desc_tip' => true,
		'custom_attributes' => array(
			'data-placeholder' => _x('Number of periods', 'Gateway setting', 'ry-woocommerce-tools'),
		),
	)
);
