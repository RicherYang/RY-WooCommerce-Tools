<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_admin {
	public static function init() {
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/ecpay-shipping-meta-box.php');

		add_filter('woocommerce_admin_shipping_fields', [__CLASS__, 'set_cvs_shipping_fields'], 99);
		add_action('woocommerce_shipping_zone_method_status_toggled', [__CLASS__, 'check_can_enable'], 10, 4);
		add_action('woocommerce_update_options_shipping_options', [__CLASS__, 'check_ship_destination']);
		add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
		add_action('woocommerce_order_action_get_new_cvs_no', ['RY_ECPay_Shipping_Api', 'get_cvs_code']);
		add_action('woocommerce_order_action_get_new_cvs_no_cod', ['RY_ECPay_Shipping_Api', 'get_cvs_code_cod']);
		add_action('woocommerce_order_action_send_at_cvs_email', ['RY_ECPay_Shipping', 'send_at_cvs_email']);

		add_action('wp_ajax_RY_ECPay_Shipping_print', [__CLASS__, 'print_info']);

		add_action('add_meta_boxes', ['RY_ECPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);
	}

	public static function set_cvs_shipping_fields($shipping_fields) {
		global $theorder;

		$shipping_method = false;
		if( !empty($theorder) ) {
			$items_shipping = $theorder->get_items('shipping');
			$items_shipping = array_shift($items_shipping);
			if( $items_shipping ) {
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($items_shipping);
			}
			if( $shipping_method !== false ) {
				$shipping_fields['cvs_store_ID'] = [
					'label' => __('Store ID', 'ry-woocommerce-tools'),
					'show' => false
				];
				$shipping_fields['cvs_store_name'] = [
					'label' => __('Store Name', 'ry-woocommerce-tools'),
					'show' => false
				];
				$shipping_fields['cvs_store_address'] = [
					'label' => __('Store Address', 'ry-woocommerce-tools'),
					'show' => false
				];
				$shipping_fields['cvs_store_telephone'] = [
					'label' => __('Store Telephone', 'ry-woocommerce-tools'),
					'show' => false
				];
				$shipping_fields['phone'] = [
					'label' => __('Phone', 'ry-woocommerce-tools')
				];
			} elseif( 'yes' == RY_WT::get_option('ecpay_keep_shipping_phone', 'no') ) {
				$shipping_fields['phone'] = [
					'label' => __('Phone', 'ry-woocommerce-tools')
				];
			}
		}
		return $shipping_fields;
	}

	public static function check_can_enable($instance_id, $method_id, $zone_id, $is_enabled) {
		if( array_key_exists($method_id, RY_ECPay_Shipping::$support_methods) ) {
			if( $is_enabled == 1 ) {
				if( 'billing_only' === get_option('woocommerce_ship_to_destination') ) {
					global $wpdb;

					$wpdb->update(
						$wpdb->prefix . 'woocommerce_shipping_zone_methods',
						[
							'is_enabled' => 0
						],
						[
							'instance_id' => absint($instance_id)
						]
					);
				}
			}
		}
	}

	public static function check_ship_destination() {
		global $wpdb;
		if( 'billing_only' === get_option('woocommerce_ship_to_destination') ) {
			RY_WT::update_option('ecpay_shipping_cvs_type', 'disable');
			foreach( ['ry_ecpay_shipping_cvs_711', 'ry_ecpay_shipping_cvs_hilife', 'ry_ecpay_shipping_cvs_family'] as $method_id ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_zone_methods',
					[
						'is_enabled' => 0
					],
					[
						'method_id' => $method_id,
					]
				);
			}
			
			WC_Admin_Settings::add_error(__('All cvs shipping methods set to disable.', 'ry-woocommerce-tools'));
		} else {
			if( RY_WT::get_option('ecpay_shipping_cvs_type') == 'disable' ) {
				RY_WT::update_option('ecpay_shipping_cvs_type', 'C2C');
			}
		}
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
				if( $theorder->has_status(['ry-at-cvs']) ) {
					$order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
				}
			}
		}
		return $order_actions;
	}

	public static function print_info() {
		$order_ID = (int) $_GET['orderid'];
		$logistics_id = (int) $_GET['id'];
		$only = isset($_GET['only']) ? (bool) $_GET['only'] : false;

		$print_info = '';
		if( $order = wc_get_order($order_ID) ) {
			foreach( $order->get_items('shipping') as $item_id => $item ) {
				$shipping_list = $order->get_meta('_ecpay_shipping_info', true);
				if( is_array($shipping_list) ) {
					foreach( $shipping_list as $info ) {
						if( $info['ID'] == $logistics_id ) {
							if( $only ) {
								$print_info = RY_ECPay_Shipping_Api::get_print_info($logistics_id, $info);
								switch( $info['LogisticsSubType'] ) {
									case 'FAMIC2C':
										$sub_info = substr($print_info, strpos($print_info, '<img'));
										preg_match('/(<img[^>]*>)/', $sub_info, $match);
										if( count($match) == 2 ) {
											$print_info = '<!DOCTYPE html><html><head><meta charset="' . get_bloginfo('charset', 'display') . '"></head><body style="margin:0;padding:0;overflow:hidden">'
												. $match[1]
												. '</body></html>';
										}
										break;
									case 'HILIFEC2C':
										$sub_info = substr($print_info, strpos($print_info, 'location.href'));
										preg_match("/'([^']*)'/", $sub_info, $match);
										if( count($match) == 2 ) {
											$print_info = '<!DOCTYPE html><html><head><meta charset="' . get_bloginfo('charset', 'display') . '"></head><body style="margin:0;padding:0;overflow:hidden">'
												. '<iframe src="' . $match[1] . '" style="border:0;width:990px;height:315px"></iframe>'
												. '</body></html>';
										}
										break;
									case 'UNIMARTC2C':
										break;
								}
							} else {
								$print_info = RY_ECPay_Shipping_Api::get_print_info_form($logistics_id, $info);
							}
							echo($print_info);
							wp_die();
						}
					}
				}
			}
			wp_redirect(admin_url('post.php?post=' . $order_ID . '&action=edit'));
			exit();
		}
		
		wp_redirect(admin_url('edit.php?post_type=shop_order'));
		exit();
	}
}

RY_ECPay_Shipping_admin::init();
