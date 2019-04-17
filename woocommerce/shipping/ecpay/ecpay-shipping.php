<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_ECPay_Shipping {
	public static $log_enabled = false;
	public static $log = false;
	public static $testmode;

	public static $support_methods = [
		'ry_ecpay_shipping_cvs_711' => 'RY_ECPay_Shipping_CVS_711',
		'ry_ecpay_shipping_cvs_hilife' => 'RY_ECPay_Shipping_CVS_Hilife',
		'ry_ecpay_shipping_cvs_family' => 'RY_ECPay_Shipping_CVS_Family'
	];

	protected static $js_data;

	public static function init() {
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-api.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-response.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-cvs-base.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-711.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-hilife.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-family.php');

		self::$log_enabled = 'yes' === RY_WT::get_option('ecpay_shipping_log', 'no');

		add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
		add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
		add_action('woocommerce_update_options_rytools_ecpay_shipping', [__CLASS__, 'check_option']);

		add_filter('wc_order_statuses', [__CLASS__, 'add_order_statuses']);
		add_filter('woocommerce_reports_order_statuses', [__CLASS__, 'add_reports_order_statuses']);
		self::register_order_statuses();

		if( 'yes' === RY_WT::get_option('ecpay_shipping_cvs', 'yes') ) {
			RY_ECPay_Shipping_Response::init();

			add_filter('woocommerce_shipping_methods', [__CLASS__, 'add_method']);

			add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_cvs_info']);
			add_action('woocommerce_checkout_process', [__CLASS__, 'is_need_checkout_fields']);
			add_action('woocommerce_review_order_after_shipping', [__CLASS__, 'shipping_chouse_cvs']);
			add_filter('woocommerce_update_order_review_fragments', [__CLASS__, 'shipping_chouse_cvs_info']);
			add_action('woocommerce_checkout_create_order', [__CLASS__, 'save_cvs_info'], 20, 2);

			if( 'yes' === RY_WT::get_option('ecpay_shipping_auto_get_no', 'yes') ) {
				add_action('woocommerce_order_status_processing', [__CLASS__, 'get_cvs_code'], 10, 2);
			}
			add_action('woocommerce_order_status_ry-at-cvs', [__CLASS__, 'send_at_cvs_email'], 10, 2);

			add_filter('woocommerce_email_classes', [__CLASS__, 'add_email_class']);
			add_filter('woocommerce_email_actions', [__CLASS__, 'add_email_action']);
		}

		add_filter('woocommerce_get_order_address', [__CLASS__, 'show_store_in_address'], 10, 3);
		add_filter('woocommerce_formatted_address_replacements', [__CLASS__, 'add_cvs_address_replacements'], 10, 2);

		if( is_admin() ) {
			require_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-admin.php');
			RY_ECPay_Shipping_admin::init();
		} else {
			wp_register_script('ry-ecpay-shipping', RY_WT_PLUGIN_URL . 'style/js/ry_ecpay_shipping.js', ['jquery'], RY_WT_VERSION, true);
		}
	}

	public static function log($message, $level = 'info') {
		if( self::$log_enabled ) {
			if( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log($level, $message, [
				'source' => 'ry_ecpay_shipping',
				'_legacy' => true
			]);
		}
	}

	public static function add_sections($sections) {
		$sections['ecpay_shipping'] = __('ECPay shipping options', 'ry-woocommerce-tools');
		return $sections;
	}

	public static function add_setting($settings, $current_section) {
		if( $current_section == 'ecpay_shipping' ) {
			$settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping.php');
			if( 'billing_only' === get_option('woocommerce_ship_to_destination') ) {
				$key = array_search(RY_WT::$option_prefix . 'ecpay_shipping_cvs_type', array_column($settings, 'id'));
				$settings[$key]['options'] = [
					'disable' => _x('Disable', 'Cvs type', 'ry-woocommerce-tools')
				];
				$settings[$key]['desc'] = __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
			}
		}
		return $settings;
	}

	public static function check_option() {
		if( 'yes' == RY_WT::get_option('ecpay_shipping_cvs', 'yes') ) {
			$enable = true;
			$name = RY_WT::get_option('ecpay_shipping_sender_name');
			if( mb_strwidth($name) < 1 || mb_strwidth($name) > 10 ) {
				$enable = false;
				WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Name length between 1 to 10 letter (5 if chinese)', 'ry-woocommerce-tools'));
				RY_WT::update_option('ecpay_shipping_sender_name', '');
			}
			if( !empty(RY_WT::get_option('ecpay_shipping_sender_phone')) ) {
				if( 1 !== preg_match('@^\(0\d{1,2}\)\d{6,8}(#\d+)?$@', RY_WT::get_option('ecpay_shipping_sender_phone')) ) {
					$enable = false;
					WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Phone format (0x)xxxxxxx#xx', 'ry-woocommerce-tools'));
					RY_WT::update_option('ecpay_shipping_sender_phone', '');
				}
			}
			if( 1 !== preg_match('@^09\d{8}?$@', RY_WT::get_option('ecpay_shipping_sender_cellphone')) ) {
				$enable = false;
				WC_Admin_Settings::add_error(__('Verification failed!', 'ry-woocommerce-tools') . ' ' . __('Cellphone format 09xxxxxxxx', 'ry-woocommerce-tools'));
				RY_WT::update_option('ecpay_shipping_sender_cellphone', '');
			}
			if( 'yes' !== RY_WT::get_option('ecpay_shipping_testmode', 'yes') ) {
				if( empty(RY_WT::get_option('ecpay_shipping_MerchantID')) ) {
					$enable = false;
				}
				if( empty(RY_WT::get_option('ecpay_shipping_HashKey')) ) {
					$enable = false;
				}
				if( empty(RY_WT::get_option('ecpay_shipping_HashIV')) ) {
					$enable = false;
				}
			}
			if( !$enable ) {
				WC_Admin_Settings::add_error(__('ECPay shipping method failed to enable!', 'ry-woocommerce-tools'));
				RY_WT::update_option('ecpay_shipping_cvs', 'no');
			}
		}
		if( !preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('ecpay_shipping_order_prefix')) ) {
			WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed allowed', 'ry-woocommerce-tools'));
			RY_WT::update_option('ecpay_shipping_order_prefix', '');
		}
	}

	public static function add_order_statuses($order_statuses) {
		$order_statuses['wc-ry-at-cvs'] = _x('Wait pickup (cvs)', 'Order status', 'ry-woocommerce-tools');
		$order_statuses['wc-ry-out-cvs'] = _x('Overdue return (cvs)', 'Order status', 'ry-woocommerce-tools');

		return $order_statuses;
	}

	public static function add_reports_order_statuses($order_statuses) {
		$order_statuses[] = 'wc-ry-at-cvs';
		$order_statuses[] = 'wc-ry-out-cvs';

		return $order_statuses;
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

	public static function add_method($shipping_methods) {
		$shipping_methods = array_merge($shipping_methods, self::$support_methods);

		return $shipping_methods;
	}

	public static function get_ecpay_api_info() {
		self::$testmode = 'yes' === RY_WT::get_option('ecpay_shipping_testmode', 'yes');
		$cvs_type = RY_WT::get_option('ecpay_shipping_cvs_type');
		if( self::$testmode ) {
			if( 'C2C' == $cvs_type ) {
				$MerchantID = '2000933';
				$HashKey = 'XBERn1YOvpM9nfZc';
				$HashIV = 'h1ONHk4P4yqbl5LK';
			} else {
				$MerchantID = '2000132';
				$HashKey = '5294y06JbISpM5x9';
				$HashIV = 'v77hoKGq4kWxNNIS';
			}
		} else {
			$MerchantID = RY_WT::get_option('ecpay_shipping_MerchantID');
			$HashKey = RY_WT::get_option('ecpay_shipping_HashKey');
			$HashIV = RY_WT::get_option('ecpay_shipping_HashIV');
		}

		return [$MerchantID, $HashKey, $HashIV, $cvs_type];
	}

	protected static function get_chosen_method() {
		static $chosen_method = null;

		if( $chosen_method === null ) {
			$packages = WC()->shipping->get_packages();
			foreach ( $packages as $i => $package ) {
				if( isset(WC()->session->chosen_shipping_methods[$i]) ) {
					$chosen_method = $package['rates'][WC()->session->chosen_shipping_methods[$i]];
					break;
				}
			}

			if( $chosen_method === null ) {
				$chosen_method = '';
			} else {
				if ( version_compare(WC_VERSION, '3.2.0', '<' ) ) {
					$chosen_method = $chosen_method->method_id;
				} else {
					$chosen_method = $chosen_method->get_method_id();
				}
			}
		}

		return $chosen_method;
	}

	public static function shipping_chouse_cvs() {
		wp_enqueue_script('ry-ecpay-shipping');
		$method = self::get_chosen_method();
		self::$js_data = [];

		if( array_key_exists($method, self::$support_methods) ) {
			wc_get_template('cart/cart-chouse-cvs.php', [], '', RY_WT_PLUGIN_DIR . 'templates/');

			list($MerchantID, $HashKey, $HashIV, $CVS_type) = self::get_ecpay_api_info();
			$method_class = self::$support_methods[$method];
			if( self::$testmode ) {
				self::$js_data['postUrl'] = RY_ECPay_Shipping_Api::$api_test_url['map'];
			} else {
				self::$js_data['postUrl'] = RY_ECPay_Shipping_Api::$api_url['map'];
			}
			self::$js_data['postData'] = [
				'MerchantID' => $MerchantID,
				'LogisticsType' => $method_class::$LogisticsType,
				'LogisticsSubType' => $method_class::$LogisticsSubType . (('C2C' == $CVS_type) ? 'C2C' : ''),
				'IsCollection' => 'Y',
				'ServerReplyURL' => esc_url(wc_get_page_permalink('checkout')),
				'Device' => (int) wp_is_mobile()
			];
		}
	}

	public static function shipping_chouse_cvs_info($fragments) {
		if( !empty(self::$js_data) ) {
			$fragments['ecpay_shipping_info'] = self::$js_data;
		}

		return $fragments;
	}

	public static function add_cvs_info($fields) {
		$fields['shipping']['LogisticsSubType'] = [
			'required' => false,
			'type' => 'hidden'
		];
		$fields['shipping']['CVSStoreID'] = [
			'required' => false,
			'type' => 'hidden'
		];
		$fields['shipping']['shipping_phone'] = [
			'label' => __('Phone', 'woocommerce'),
			'required' => true,
			'type' => 'tel',
			'validate' => ['phone'],
			'class' => ['form-row-wide', 'cvs-info'],
			'priority' => 100
		];
		$fields['shipping']['CVSStoreName'] = [
			'label' => __('Store Name', 'ry-woocommerce-tools'),
			'required' => false,
			'type' => 'hiddentext',
			'class' => ['form-row-wide', 'cvs-info'],
			'priority' => 110
		];
		$fields['shipping']['CVSAddress'] = [
			'label' => __('Store Address', 'ry-woocommerce-tools'),
			'required' => false,
			'type' => 'hiddentext',
			'class' => ['form-row-wide', 'cvs-info'],
			'priority' => 111
		];
		$fields['shipping']['CVSTelephone'] = [
			'label' => __('Store Telephone', 'ry-woocommerce-tools'),
			'required' => false,
			'type' => 'hiddentext',
			'class' => ['form-row-wide', 'cvs-info'],
			'priority' => 112
		];

		return $fields;
	}

	public static function is_need_checkout_fields() {
		$used_cvs = false;
		$shipping_method = isset($_POST['shipping_method']) ? wc_clean( $_POST['shipping_method'] ) : [];
		foreach( $shipping_method as $method ) {
			$method = strstr($method, ':', true);
			if( array_key_exists($method, self::$support_methods) ) {
				$used_cvs = true;
				break;
			}
		}

		if( $used_cvs ) {
			add_filter('woocommerce_checkout_fields', [__CLASS__, 'fix_add_cvs_info'], 15);
		} else {
			add_filter('woocommerce_checkout_fields', [__CLASS__, 'fix_noin_add_cvs_info'], 15);
		}
	}

	public static function fix_add_cvs_info($fields) {
		$fields['shipping']['shipping_country']['required'] = false;
		$fields['shipping']['shipping_address_1']['required'] = false;
		$fields['shipping']['shipping_address_2']['required'] = false;
		$fields['shipping']['shipping_city']['required'] = false;
		$fields['shipping']['shipping_state']['required'] = false;
		$fields['shipping']['shipping_postcode']['required'] = false;

		$fields['shipping']['shipping_phone']['required'] = true;
		$fields['shipping']['CVSStoreName']['required'] = true;
		return $fields;
	}

	public static function fix_noin_add_cvs_info($fields) {
		$fields['shipping']['shipping_phone']['required'] = false;
		return $fields;
	}

	public static function save_cvs_info($order, $data) {
		if( !empty($data['CVSStoreID']) ) {
			$order->set_shipping_company('');
			$order->set_shipping_address_2('');
			$order->set_shipping_city('');
			$order->set_shipping_state('');
			$order->set_shipping_postcode('');

			$order->add_order_note(sprintf(
				/* translators: 1: Store name 2: Store ID */
				__('CVS store %1$s (%2$s)', 'ry-woocommerce-tools'),
				$data['CVSStoreName'],
				$data['CVSStoreID']
			));
			$order->update_meta_data('_shipping_cvs_store_ID', $data['CVSStoreID']);
			$order->update_meta_data('_shipping_cvs_store_name', $data['CVSStoreName']);
			$order->update_meta_data('_shipping_cvs_store_address', $data['CVSAddress']);
			$order->update_meta_data('_shipping_cvs_store_telephone', $data['CVSTelephone']);
			$order->update_meta_data('_shipping_phone', $data['shipping_phone']);
			$order->set_shipping_address_1($data['CVSAddress']);
		}
	}

	public static function show_store_in_address($address, $type, $order) {
		if( $type == 'shipping' ) {
			$items_shipping = $order->get_items('shipping');
			if( count($items_shipping) ) {
				$items_shipping = array_shift($items_shipping);
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($items_shipping);
			}
			if( !empty($shipping_method) ) {
				$shipping_methods = WC()->shipping->get_shipping_methods();

				$address['shipping_type'] = $shipping_methods[$shipping_method]->get_method_title();
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
		if( isset($args['cvs_store_name']) ) {
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

	public static function get_order_support_shipping($items) {
		foreach( self::$support_methods as $method => $method_class ) {
			if( strpos($items->get_method_id(), $method ) === 0 ) {
				return $method;
			}
		}

		return false;
	}

	public static function get_cvs_code($order_id, $order) {
		$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
		if( !is_array($cvs_info_list) ) {
			$cvs_info_list = [];
		}
		if( count($cvs_info_list) == 0 ) {
			RY_ECPay_Shipping_Api::get_cvs_code($order_id);
		}
	}

	public static function send_at_cvs_email($order_id, $order = null) {
		if( !is_object($order) ) {
			$order = wc_get_order($order_id);
		}
		do_action('ry_ecpay_shipping_cvs_to_store', $order_id, $order);
	}

	public static function add_email_class($emails) {
		$emails['RY_ECPay_Shipping_Email_Customer_CVS_Store'] = include(RY_WT_PLUGIN_DIR . 'woocommerce/emails/ecpay-shipping-customer-cvs-store.php');

		return $emails;
	}

	public static function add_email_action($actions) {
		$actions[] = 'ry_ecpay_shipping_cvs_to_store';

		return $actions;
	}
}

RY_ECPay_Shipping::init();
