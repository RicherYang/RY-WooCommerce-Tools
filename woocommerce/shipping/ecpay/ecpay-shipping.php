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
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ry-base.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-ecpay.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-api.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-response.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-cvs-base.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-711.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-hilife.php');
		include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping-cvs-family.php');

		self::$log_enabled = 'yes' === RY_WT::get_option('ecpay_shipping_log', 'no');

		if( 'yes' === RY_WT::get_option('ecpay_shipping_cvs', 'no') ) {
			RY_ECPay_Shipping_Response::init();

			add_filter('woocommerce_shipping_methods', [__CLASS__, 'add_method']);

			add_filter('woocommerce_checkout_fields', [__CLASS__, 'add_cvs_info']);
			add_action('woocommerce_checkout_process', [__CLASS__, 'is_need_checkout_fields']);
			add_action('woocommerce_review_order_after_shipping', [__CLASS__, 'shipping_choose_cvs']);
			add_filter('woocommerce_update_order_review_fragments', [__CLASS__, 'shipping_choose_cvs_info']);
			add_action('woocommerce_checkout_create_order', [__CLASS__, 'save_cvs_info'], 20, 2);

			if( 'yes' === RY_WT::get_option('ecpay_shipping_auto_get_no', 'yes') ) {
				add_action('woocommerce_order_status_processing', [__CLASS__, 'get_cvs_code'], 10, 2);
			}
			add_action('woocommerce_order_status_ry-at-cvs', [__CLASS__, 'send_at_cvs_email'], 10, 2);

			add_filter('woocommerce_email_classes', [__CLASS__, 'add_email_class']);
			add_filter('woocommerce_email_actions', [__CLASS__, 'add_email_action']);
		}

		if( is_admin() ) {
			add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections']);
			add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
			add_action('woocommerce_update_options_rytools_ecpay_shipping', [__CLASS__, 'check_option']);

			include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/ecpay-shipping-admin.php');
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

	public static function shipping_choose_cvs() {
		$chosen_shipping = wc_get_chosen_shipping_method_ids();
		$chosen_shipping = array_intersect($chosen_shipping, array_keys(self::$support_methods));
		$chosen_shipping = array_shift($chosen_shipping);
		self::$js_data = [];

		if( $chosen_shipping ) {
			wc_get_template('cart/cart-choose-cvs.php', [], '', RY_WT_PLUGIN_DIR . 'templates/');

			list($MerchantID, $HashKey, $HashIV, $CVS_type) = self::get_ecpay_api_info();
			$method_class = self::$support_methods[$chosen_shipping];

			self::$js_data['postData'] = [
				'MerchantID' => $MerchantID,
				'LogisticsType' => $method_class::$LogisticsType,
				'LogisticsSubType' => $method_class::$LogisticsSubType . (('C2C' == $CVS_type) ? 'C2C' : ''),
				'IsCollection' => 'Y',
				'ServerReplyURL' => esc_url(wc_get_page_permalink('checkout')),
				'Device' => (int) wp_is_mobile()
			];
		}

		wp_localize_script('ry-shipping', 'ry_shipping_params', array_merge([
			'postUrl' => RY_ECPay_Shipping_Api::get_map_post_url()
		], self::$js_data));

		wp_enqueue_script('ry-shipping');
	}

	public static function shipping_choose_cvs_info($fragments) {
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
			'label' => __('Phone', 'ry-woocommerce-tools'),
			'required' => true,
			'type' => 'tel',
			'validate' => ['phone'],
			'class' => ['form-row-wide'],
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

		if( 'no' == RY_WT::get_option('ecpay_keep_shipping_phone', 'no') ) {
			$fields['shipping']['shipping_phone']['class'][] = 'cvs-info';
		}

		if( is_checkout()) {
			$chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
			$is_support = false;
			if( count($chosen_method) ) {
				foreach( self::$support_methods as $method => $method_class ) {
					if( strpos($chosen_method[0], $method ) === 0 ) {
						$is_support = true;
					}
				}
			}
			if( $is_support ) {
				foreach($fields['shipping'] as $key => $filed ) {
					if (isset($filed['class'])) {
						if( !in_array('cvs-info', $filed['class'])) {
							if( !in_array($key, ['shipping_first_name', 'shipping_last_name', 'shipping_country', 'shipping_phone'])) {
								$fields['shipping'][$key]['class'][] = 'ry-hide';
							}
						}
					} else {
						if($filed['type'] != 'hidden') {
							$fields['shipping'][$key]['class'] = ['ry-hide'];
						}
					}
				}
			} else {
				$fields['shipping']['CVSStoreName']['class'][] = 'ry-hide';
				$fields['shipping']['CVSAddress']['class'][] = 'ry-hide';
				$fields['shipping']['CVSTelephone']['class'][] = 'ry-hide';
			}
		}

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
			add_filter('woocommerce_checkout_fields', [__CLASS__, 'fix_add_cvs_info'], 9999);
		} else {
			add_filter('woocommerce_checkout_fields', [__CLASS__, 'fix_noin_add_cvs_info'], 9999);
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

	public static function get_order_support_shipping($items) {
		foreach( self::$support_methods as $method => $method_class ) {
			if( strpos($items->get_method_id(), $method ) === 0 ) {
				return $method;
			}
		}

		return false;
	}

	public static function get_cvs_code($order_id, $order) {
		$shipping_list = $order->get_meta('_ecpay_shipping_info', true);
		if( !is_array($shipping_list) ) {
			$shipping_list = [];
		}
		if( count($shipping_list) == 0 ) {
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
