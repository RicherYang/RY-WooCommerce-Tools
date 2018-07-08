<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_admin {
	public static function init() {
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/ecpay-shipping-meta-box.php');

		add_filter('woocommerce_shipping_address_map_url_parts', array(__CLASS__, 'fix_cvs_map_address'));

		add_filter('woocommerce_admin_order_actions', array(__CLASS__, 'add_admin_order_actions'), 10, 2);
		add_filter('woocommerce_admin_shipping_fields', array(__CLASS__, 'set_cvs_shipping_fields'));
		add_filter('woocommerce_order_actions', array(__CLASS__, 'add_order_actions'));
		add_action('woocommerce_order_action_get_new_cvs_no', array('RY_ECPay_Shipping_Api', 'get_cvs_code'));
		add_action('woocommerce_order_action_get_new_cvs_no_cod', array('RY_ECPay_Shipping_Api', 'get_cvs_code_cod'));
		add_action('woocommerce_order_action_send_at_cvs_email', array('RY_ECPay_Shipping', 'send_at_cvs_email'));
		add_action('wp_ajax_RY_ECPay_Shipping_print', array('RY_ECPay_Shipping_Api', 'print_info'));

		add_action('admin_enqueue_scripts', array(__CLASS__, 'add_scripts'));
		add_action('add_meta_boxes', array('RY_ECPay_Shipping_Meta_Box', 'add_meta_box'), 40, 2);
	}

	public static function fix_cvs_map_address($address) {
		if( isset($address['cvs_address']) ) {
			$address = array(
				$address['cvs_address']
			);
		}
		return $address;
	}

	public static function add_admin_order_actions($actions, $object) {
		if( $object->has_status(array('ry-at-cvs')) ) {
			$actions['complete'] = array(
				'url' => wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $object->get_id()), 'woocommerce-mark-order-status'),
				'name' => __('Complete', 'woocommerce'),
				'action' => 'complete',
			);
		}

		return $actions;
	}

	public static function set_cvs_shipping_fields($shipping_fields) {
		global $theorder;

		$shipping_method = false;
		if( !empty($theorder) ) {
			$items_shipping = $theorder->get_items('shipping');
			if( count($items_shipping) ) {
				$items_shipping = array_shift($items_shipping);
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($items_shipping);
			}
			if( $shipping_method !== false ) {
				unset($shipping_fields['company']);
				unset($shipping_fields['address_1']);
				unset($shipping_fields['address_2']);
				unset($shipping_fields['city']);
				unset($shipping_fields['postcode']);
				unset($shipping_fields['country']);
				unset($shipping_fields['state']);
				$shipping_fields['cvs_store_ID'] = array(
					'label' => __('Store ID', 'ry-woocommerce-tools'),
					'show' => false
				);
				$shipping_fields['cvs_store_name'] = array(
					'label' => __('Store Name', 'ry-woocommerce-tools'),
					'show' => false
				);
				$shipping_fields['cvs_store_address'] = array(
					'label' => __('Store Address', 'ry-woocommerce-tools'),
					'show' => false
				);
				$shipping_fields['cvs_store_telephone'] = array(
					'label' => __('Store Telephone', 'ry-woocommerce-tools'),
					'show' => false
				);
				$shipping_fields['phone'] = array(
					'label' => __('Phone', 'woocommerce')
				);
			}
		}
		return $shipping_fields;
	}

	public static function add_order_actions($order_actions) {
		global $theorder;
		if( !is_object($theorder) ) {
			$theorder = wc_get_order($post->ID);
		}

		foreach( $theorder->get_items('shipping') as $item_id => $item ) {
			if( RY_ECPay_Shipping::get_order_support_shipping($item) !== false ) {
				$order_actions['get_new_cvs_no'] = __('Get new CVS payment no', 'ry-woocommerce-tools');
				if( $theorder->get_payment_method() == 'cod' ) {
					$order_actions['get_new_cvs_no_cod'] = __('Get new CVS payment no with cod', 'ry-woocommerce-tools');
				}
				if( $theorder->has_status(array('ry-at-cvs')) ) {
					$order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
				}
			}
		}
		return $order_actions;
	}

	public static function add_scripts() {
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if( in_array($screen_id, array('shop_order', 'edit-shop_order')) ) {
			wp_enqueue_style('ry-shipping-admin-style', RY_WT_PLUGIN_URL . 'style/admin/ry_shipping.css', array(), RY_WT_VERSION);
			wp_enqueue_script('ry-ecpay-shipping-admin', RY_WT_PLUGIN_URL . 'style/js/admin/ry_ecpay_shipping.js', array('jquery'), RY_WT_VERSION);
		}
	}
}
