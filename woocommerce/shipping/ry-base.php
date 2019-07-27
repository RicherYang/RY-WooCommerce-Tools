<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_Shipping {
	public static function init() {
		add_filter('wc_order_statuses', [__CLASS__, 'add_order_statuses']);
		add_filter('woocommerce_reports_order_statuses', [__CLASS__, 'add_reports_order_statuses']);
		add_filter('woocommerce_order_is_paid_statuses', [__CLASS__, 'add_order_is_paid_statuses']);
		self::register_order_statuses();

		add_filter('woocommerce_get_order_address', [__CLASS__, 'show_store_in_address'], 10, 3);
		add_filter('woocommerce_formatted_address_replacements', [__CLASS__, 'add_cvs_address_replacements'], 10, 2);

		if( is_admin() ) {
			add_action('admin_enqueue_scripts', [__CLASS__, 'add_scripts']);

			add_filter('woocommerce_shipping_address_map_url_parts', [__CLASS__, 'fix_cvs_map_address']);
			add_filter('woocommerce_admin_order_actions', [__CLASS__, 'add_admin_order_actions'], 10, 2);
		} else {
			wp_register_script('ry-shipping', RY_WT_PLUGIN_URL . 'style/js/ry_shipping.js', ['jquery'], RY_WT_VERSION, true);
		}
	}

	public static function add_order_statuses($order_statuses) {
		$order_statuses['wc-ry-at-cvs'] = _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools');
		$order_statuses['wc-ry-out-cvs'] = _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools');

		return $order_statuses;
	}

	public static function add_reports_order_statuses($order_statuses) {
		$order_statuses[] = 'ry-at-cvs';
		$order_statuses[] = 'ry-out-cvs';

		return $order_statuses;
	}

	public static function add_order_is_paid_statuses($statuses) {
		$statuses[] = 'ry-at-cvs';

		return $statuses;
	}

	public static function register_order_statuses() {
		register_post_status('wc-ry-at-cvs', [
			'label' => _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools'),
			'public' => false,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count' => _n_noop('Wait pickup (cvs) <span class="count">(%s)</span>', 'Wait pickup (cvs) <span class="count">(%s)</span>', 'ry-woocommerce-tools'),
		]);
		register_post_status('wc-ry-out-cvs', [
			'label' => _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools'),
			'public' => false,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count' => _n_noop('Overdue return (cvs) <span class="count">(%s)</span>', 'Overdue return (cvs) <span class="count">(%s)</span>', 'ry-woocommerce-tools'),
		]);
	}

	public static function show_store_in_address($address, $type, $order) {
		if( $type == 'shipping' ) {
			if( $order->get_meta('_shipping_cvs_store_ID') != '' ) {
				$items_shipping = $order->get_items('shipping');
				$items_shipping = array_shift($items_shipping);
				if ( version_compare(WC_VERSION, '3.2.0', '<' ) ) {
					$items_shipping = $items_shipping->method_id;
				} else {
					$items_shipping = $items_shipping->get_method_id();
				}

				$shipping_methods = WC()->shipping->get_shipping_methods();

				$address['shipping_type'] = $shipping_methods[$items_shipping]->get_method_title();
				$address['cvs_store_ID'] = $order->get_meta('_shipping_cvs_store_ID');
				$address['cvs_store_name'] = $order->get_meta('_shipping_cvs_store_name');
				$address['cvs_address'] = $order->get_meta('_shipping_cvs_store_address');
				$address['cvs_telephone'] = $order->get_meta('_shipping_cvs_store_telephone');
				$address['phone'] = $order->get_meta('_shipping_phone');
				$address['country'] = 'CVS';
			}
		}

		return $address;
	}

	public static function add_cvs_address_replacements($replacements, $args) {
		if( isset($args['cvs_store_ID']) ) {
			if( isset($args['shipping_type']) ) {
				$replacements['{shipping_type}'] = $args['shipping_type'];
			}
			$replacements['{cvs_store_ID}'] = $args['cvs_store_ID'];
			$replacements['{cvs_store_name}'] = $args['cvs_store_name'];
			$replacements['{cvs_store_address}'] = $args['cvs_address'];
			$replacements['{cvs_store_telephone}'] = $args['cvs_telephone'];
			$replacements['{phone}'] = $args['phone'];
		}
		return $replacements;
	}

	public static function add_scripts() {
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if( in_array($screen_id, ['shop_order', 'edit-shop_order']) ) {
			wp_enqueue_style('ry-shipping-admin-style', RY_WT_PLUGIN_URL . 'style/admin/ry_shipping.css', [], RY_WT_VERSION);
			wp_enqueue_script('ry-shipping-admin', RY_WT_PLUGIN_URL . 'style/js/admin/ry_shipping.js', ['jquery'], RY_WT_VERSION);
		}
	}

	public static function fix_cvs_map_address($address) {
		if( isset($address['cvs_address']) ) {
			$address = [
				$address['cvs_address']
			];
		}
		return $address;
	}

	public static function add_admin_order_actions($actions, $object) {
		if( $object->has_status(['ry-at-cvs']) ) {
			$actions['complete'] = [
				'url' => wp_nonce_url(admin_url('admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $object->get_id()), 'woocommerce-mark-order-status'),
				'name' => __('Complete', 'woocommerce'),
				'action' => 'complete',
			];
		}

		return $actions;
	}
}

RY_Shipping::init();
