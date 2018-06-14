<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_WT {
	public static $options = array();
	public static $textdomain = 'ry-woocommerce-tools';
	public static $option_prefix = 'RY_WT_';

	private static $initiated = false;

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			load_plugin_textdomain(self::$textdomain, false, plugin_basename(dirname(RY_WT_PLUGIN_BASENAME)) . '/languages');

			if( !defined('WC_VERSION') ) {
				add_action('admin_notices', array(__CLASS__, 'need_woocommerce'));
				return;
			}

			include_once(RY_WT_PLUGIN_DIR . 'class.ry-wt.update.php');
			RY_WT_update::update();

			if( is_admin() ) {
				include_once(RY_WT_PLUGIN_DIR . 'class.ry-wt.admin.php');
				RY_WT_admin::init();
			}

			add_action('ry_check_ntp_time', array(__CLASS__, 'check_ntp_time'));
			if( self::get_option('ntp_time_error', false) ) {
				add_action('admin_notices', array(__CLASS__, 'ntp_time_error'));
			}

			// 本地化地址
			add_filter('woocommerce_localisation_address_formats', array(__CLASS__, 'add_address_format'));

			// 綠界金流
			if( 'yes' == get_option(self::$option_prefix . 'enabled_ecpay_gateway', 'yes') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway.php');
				RY_ECPay_Gateway::init();
			}
			// 綠界物流
			if( 'yes' == get_option(self::$option_prefix . 'enabled_ecpay_shipping', 'no') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping.php');
				RY_ECPay_Shipping::init();
			}

			// 重新付款
			if( 'no' == get_option(self::$option_prefix . 'repay_action', 'no') ) {
				add_filter('woocommerce_my_account_my_orders_actions', array(__CLASS__, 'remove_pay_action'));
			}
			// 取消密碼強度檢查
			if( 'no' == get_option(self::$option_prefix . 'strength_password', 'yes') ) {
				if( ( !is_admin() || defined('DOING_AJAX') ) && !defined('DOING_CRON') ) {
					add_action('wp_enqueue_scripts', array(__CLASS__, 'remove_strength_password_script'), 20);
				}
			}

			add_filter('woocommerce_form_field_hidden', array(__CLASS__, 'form_field_hidden'), 20, 4);
			add_filter('woocommerce_form_field_hidden_empty', array(__CLASS__, 'form_field_hidden_empty'), 20, 4);
			add_filter('woocommerce_form_field_hiddentext', array(__CLASS__, 'form_field_hiddentext'), 20, 4);

			// 不顯示國家選項
			if( 'no' == get_option(self::$option_prefix . 'show_country_select', 'no') ) {
				add_filter('woocommerce_billing_fields', array(__CLASS__, 'hide_country_select'), 20);
				add_filter('woocommerce_shipping_fields', array(__CLASS__, 'hide_country_select'), 20);
				add_filter('woocommerce_form_field_country_hidden', array(__CLASS__, 'form_field_country_hidden'), 20, 4);
			}
			// 先顯示姓氏
			if( 'yes' == get_option(self::$option_prefix . 'last_name_first', 'no') ) {
				add_filter('woocommerce_default_address_fields', array(__CLASS__, 'last_name_first'));
			}
		}
	}

	public static function add_address_format($address_formats) {
		$address_formats['TW'] = "{postcode}\n{country} {state} {city}\n{address_1} {address_2}\n{company} {last_name}{first_name}";
		$address_formats['CVS'] = "{last_name}{first_name}\n{cvs_store_name} ({cvs_store_ID})\n{address_1}\n{cvs_telephone}";
		if( !is_admin() ) {
			$address_formats['CVS'] .= "\n" . '<p class="woocommerce-customer-details--phone">{phone}</p>';
		}
		return $address_formats;
	}

	public static function remove_pay_action($actions) {
		if( isset($actions['pay']) ) {
			unset($actions['pay']);
		}
		return $actions;
	}

	public static function remove_strength_password_script() {
		 wp_dequeue_script('wc-password-strength-meter');
	}

	public static function hide_country_select($fields) {
		if( isset($fields['billing_country']) ) {
			$fields['billing_country']['type'] = 'country_hidden';
			$fields['billing_country']['required'] = false;
		}
		if( isset($fields['shipping_country']) ) {
			$fields['shipping_country']['type'] = 'country_hidden';
			$fields['shipping_country']['required'] = false;
		}

		return $fields;
	}

	public static function last_name_first($fields) {
		$fields['first_name']['priority'] = 20;
		$class_key = array_search('form-row-first', $fields['first_name']['class']);
		unset($fields['first_name']['class'][$class_key]);
		$fields['first_name']['class'][] = 'form-row-last';

		$fields['last_name']['priority'] = 10;
		$class_key = array_search('form-row-last', $fields['last_name']['class']);
		unset($fields['last_name']['class'][$class_key]);
		$fields['last_name']['class'][] = 'form-row-first';

		return $fields;
	}

	public static function form_field_country_hidden($field, $key, $args, $value) {
		$custom_attributes = self::form_field_custom_attributes($args);

		$country = '';
		$countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();

		if( count($countries) == 1 ) {
			$country = current(array_keys($countries));
		} else {
			foreach ( $countries as $ckey => $cvalue ) {
				if( (string) $value === (string) $ckey ) {
					$country = $ckey;
				}
			}
		}

		$field .= '<input type="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($country) . '" ' . implode(' ', $custom_attributes) . ' class="country_to_state" readonly>';

		return $field;
	}

	public static function form_field_hidden($field, $key, $args, $value) {
		$custom_attributes = self::form_field_custom_attributes($args);

		$field .= '<input type="hidden" class="' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . '>';
		return $field;
	}

	public static function form_field_hidden_empty($field, $key, $args, $value) {
		$custom_attributes = self::form_field_custom_attributes($args);

		$field .= '<input type="hidden" class="' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="" ' . implode(' ', $custom_attributes) . '>';
		return $field;
	}

	public static function form_field_hiddentext($field, $key, $args, $value) {
		$custom_attributes = self::form_field_custom_attributes($args);
		if ( $args['required'] ) {
			$args['class'][] = 'validate-required';
			$required = ' <abbr class="required" title="' . esc_attr__('required', self::$textdomain) . '">*</abbr>';
		} else {
			$required = '';
		}

		$field .= '<label class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
		$field .= '<strong>' . esc_html($value) . '</strong>';
		$field .= '<input type="hidden" class="' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . '>';

		$sort = $args['priority'] ? $args['priority'] : '';
		$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr($sort) . '">%3$s</p>';
		$container_class = esc_attr(implode(' ', $args['class']));
		$container_id = esc_attr($args['id']) . '_field';
		$field = sprintf($field_container, $container_class, $container_id, $field);

		return $field;
	}

	protected static function form_field_custom_attributes($args) {
		$custom_attributes = array();
		$args['custom_attributes'] = array_filter((array) $args['custom_attributes'], 'strlen');
		if( !empty($args['custom_attributes']) && is_array($args['custom_attributes']) ) {
			foreach( $args['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		return $custom_attributes;
	}

	public static function need_woocommerce() {
		$message = sprintf(
			__('<strong>%s</strong> is inactive. It require WooCommerce version 3.0.0 or newer.', self::$textdomain),
			__('RY WooCommerce Tools', self::$textdomain)
		);
		printf('<div class="error"><p>%s</p></div>', $message);
	}

	public static function check_ntp_time() {
		if( function_exists('stream_socket_client') ) {
			$socket = stream_socket_client('udp://time.google.com:123', $errno, $errstr);
			if( $socket ) {
				fwrite($socket, chr(0x1B) . str_repeat(chr(0x00), 47));
				$response = fread($socket, 48);
				fclose($socket);
				if( !empty($response) ) {
					$data = @unpack('N12', $response);
					if( is_array($data) && isset($data[9]) ) {
						$timestamp = sprintf('%u', $data[9]) - 2208988800;
						$time_diff = current_time('timestamp', true) - $timestamp;
						if( abs($time_diff) > MINUTE_IN_SECONDS ) {
							self::update_option('ntp_time_error', true);
						}
					}
				}
			}
		}
	}

	public static function ntp_time_error() {
		$message = sprintf(
			__('Please check your server time setting. Server time is differs from Google Public NTP  more than one minute.', self::$textdomain)
		);
		printf('<div class="error"><p>%s</p></div>', $message);
	}

	public static function get_option($option, $default = false) {
		return get_option(self::$option_prefix . $option, $default);
	}

	public static function update_option($option, $value) {
		return update_option(self::$option_prefix . $option, $value);
	}

	public static function delete_option($option) {
		return delete_option(self::$option_prefix . $option);
	}

	public static function plugin_activation() {
		if( !wp_next_scheduled('ry_check_ntp_time') ) {
			self::update_option('ntp_time_error', false);
			wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
		}
	}

	public static function plugin_deactivation( ) {
		wp_clear_scheduled_hook('ry_check_ntp_time');
	}
}
