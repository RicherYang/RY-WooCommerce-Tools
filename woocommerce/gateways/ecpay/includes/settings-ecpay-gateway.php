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
		'id' => RY_WT::$option_prefix . 'ecpay_gateway',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Enable ECPay gateway method', RY_WT::$textdomain)
	),
	array(
		'title' => __('Debug log', 'woocommerce'),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_log',
		'type' => 'checkbox',
		'default' => 'no',
		'desc' => __('Enable logging', 'woocommerce')
			. '<br>' . sprintf(__('Log ECPay gateway events/message, inside %s', RY_WT::$textdomain), '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_gateway') . '</code>')
	),
	array(
		'title' => __('Order no prefix', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_order_prefix',
		'type' => 'text',
		'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', RY_WT::$textdomain),
		'desc_tip' => true
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
		'title' => __('ECPay gateway sandbox', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_testmode',
		'type' => 'checkbox',
		'default' => 'yes',
		'desc' => __('Enable ECPay gateway sandbox', RY_WT::$textdomain)
	),
	array(
		'title' => __('MerchantID', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_MerchantID',
		'type' => 'text',
		'default' => ''
	),
	array(
		'title' => __('HashKey', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_HashKey',
		'type' => 'text',
		'default' => ''
	),
	array(
		'title' => __('HashIV', RY_WT::$textdomain),
		'id' => RY_WT::$option_prefix . 'ecpay_gateway_HashIV',
		'type' => 'text',
		'default' => ''
	),
	array(
		'id' => 'api_options',
		'type' => 'sectionend'
	)
);
