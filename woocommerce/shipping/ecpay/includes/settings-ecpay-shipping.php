<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

return array(
	array(
		'title' => __('Base options', RY_WT::$textdomain),
		'id' => 'shipping_options',
		'type' => 'title',
	),
	array(
		'title' => __('Enable/Disable', 'woocommerce'),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_cvs',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Enable ECPay shipping method', RY_WT::$textdomain)
	),
	array(
		'title' => __('Debug log', 'woocommerce'),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_log',
		'type' => 'checkbox',
		'default' => 'no',
		'desc' => __('Enable logging', 'woocommerce')
			. '<br>' . sprintf(__('Log ECPay shipping events/message, inside %s', RY_WT::$textdomain), '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_shipping') . '</code>')
	),
	array(
		'title' => __('Log status change', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_log_status_change',
		'type' => 'checkbox',
		'default' => 'no',
		'desc' => __('Log status change at order notes.', RY_WT::$textdomain)
	),
	array(
		'title' => __('Auto get shipping payment no', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_auto_get_no',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Auto get shipping payment no when order status is change to processing.', RY_WT::$textdomain)
	),
	array(
		'title' => __('Auto completed order', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_auto_completed',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Auto completed order when user getted products.', RY_WT::$textdomain)
	),
	array(
		'title' => __('Order no prefix', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_order_prefix',
		'type' => 'text',
		'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', RY_WT::$textdomain),
		'desc_tip' => true
	),
	array(
		'title' => __('Cvs shipping type', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_cvs_type',
		'type' => 'select',
		'default' => 'C2C',
		'options' => array(
			'C2C' => __('C2C', RY_WT::$textdomain),
			//'B2C' => __('B2C', RY_WT::$textdomain)
		)
	),
	array(
		'title' => __('Sender name', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_name',
		'type' => 'text',
		'desc' => __('Name length between 1 to 10 letter', RY_WT::$textdomain),
		'desc_tip' => true
	),
	array(
		'title' => __('Sender phone', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_phone',
		'type' => 'text',
		'desc' => __('Phone format (0x)xxxxxxx#xx', RY_WT::$textdomain),
		'desc_tip' => true,
		'placeholder' => '(0x)xxxxxxx#xx',
		'custom_attributes' => array(
			'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
		)
	),
	array(
		'title' => __('Sender cellphone', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_sender_cellphone',
		'type' => 'text',
		'desc' => __('Cellphone format 09xxxxxxxx', RY_WT::$textdomain),
		'desc_tip' => true,
		'placeholder' => '09xxxxxxxx',
		'custom_attributes' => array(
			'pattern' => '09\d{8}',
		)
	),
	array(
		'id' => 'shipping_options',
		'type' => 'sectionend'
	),

	array(
		'title' => __('API credentials', RY_WT::$textdomain),
		'id' => 'api_options',
		'type' => 'title'
	),
	array(
		'title' => __('ECPay shipping sandbox', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_testmode',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Enable ECPay shipping sandbox', RY_WT::$textdomain)
	),
	array(
		'title' => __('MerchantID', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_MerchantID',
		'type' => 'text',
		'default' => ''
	),
	array(
		'title' => __('HashKey', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_HashKey',
		'type' => 'text',
		'default' => ''
	),
	array(
		'title' => __('HashIV', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_shipping_HashIV',
		'type' => 'text',
		'default' => ''
	),
	array(
		'id' => 'api_options',
		'type' => 'sectionend'
	)
);