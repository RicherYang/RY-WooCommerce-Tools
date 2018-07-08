<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

$settings = array(
	'title' => array(
		'title' => __('Title', 'woocommerce'),
		'type' => 'text',
		'default' => $this->method_title,
		'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
		'desc_tip' => true
	),
	'tax_status' => array(
		'title' => __('Tax status', 'woocommerce'),
		'type' => 'select',
		'default' => 'none',
		'options' => array(
			'taxable' => __('Taxable', 'woocommerce'),
			'none' => _x('None', 'Tax status', 'woocommerce')
		),
		'class' => 'wc-enhanced-select',
	),
	'cost' => array(
		'title' => __('Shipping cost', 'ry-woocommerce-tools'),
		'type' => 'number',
		'default' => 0,
		'min' => 1,
		'step' => 1
	),
	'cost_requires' => array(
		'title' => __('Free shipping requires...', 'woocommerce'),
		'type' => 'select',
		'default' => '',
		'options' => array(
			'' => __('N/A', 'woocommerce' ),
			'min_amount' => __('A minimum order amount', 'woocommerce'),
		),
		'class' => 'wc-enhanced-select'
	),
	'min_amount' => array(
		'title' => __('Minimum order amount', 'ry-woocommerce-tools'),
		'type' => 'price',
		'default' => 0,
		'placeholder' => wc_format_localized_price(0),
		'description' => __('Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce'),
		'desc_tip' => true
	),
	'weight_plus_cost' => array(
		// translators: %s WooCommerce weight unit*
		'title' => sprintf(__('Every weight (%s) to plus times of cost', 'ry-woocommerce-tools'), __(get_option('woocommerce_weight_unit'), 'woocommerce')),
		'type' => 'number',
		'default' => 0,
		'placeholder' => 0,
		'description' => __('Calculate free shipping first. 0 to disable plus cost by weight.', 'ry-woocommerce-tools'),
		'desc_tip' => true
	)
);

$shipping_classes = WC()->shipping->get_shipping_classes();

if ( !empty($shipping_classes) ) {
	$settings['class_available'] = array(
		'title' => __('Shipping available', 'ry-woocommerce-tools'),
		'type' => 'title',
		'default' => '',
		/* translators: %s: shipping class setting url */
		'description' => sprintf(__('These shipping available based on the <a href="%s">product shipping class</a>.', 'ry-woocommerce-tools'), admin_url('admin.php?page=wc-settings&tab=shipping&section=classes')),
	);
	foreach( $shipping_classes as $shipping_class ) {
		if( !isset($shipping_class->term_id) ) {
			continue;
		}
		$settings['class_available_' . $shipping_class->term_id] = array(
			/* translators: %s: shipping class name */
			'title' => sprintf(__('"%s" available', 'ry-woocommerce-tools'), esc_html($shipping_class->name)),
			'type' => 'checkbox',
			'default' => $this->get_option('class_available_' . $shipping_class->term_id, 'yes')
		);
	}
}

return $settings;
