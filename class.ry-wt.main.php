<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_WT {
	public static $options = [];
	public static $option_prefix = 'RY_WT_';

	private static $initiated = false;

	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			load_plugin_textdomain('ry-woocommerce-tools', false, plugin_basename(dirname(RY_WT_PLUGIN_BASENAME)) . '/languages');

			if( !defined('WC_VERSION') ) {
				add_action('admin_notices', [__CLASS__, 'need_woocommerce']);
				return;
			}

			self::fixed_old_function();

			include_once(RY_WT_PLUGIN_DIR . 'class.ry-wt.update.php');
			RY_WT_update::update();

			if( is_admin() ) {
				include_once(RY_WT_PLUGIN_DIR . 'class.ry-wt.admin.php');
			} else {
				if( apply_filters('ry_show_unpay_title_notice', true) ) {
					self::add_unpay_title_notice(true);
					add_filter('woocommerce_email_setup_locale', [__CLASS__, 'remove_unpay_title_notice']);
					add_filter('woocommerce_email_restore_locale', [__CLASS__, 'add_unpay_title_notice']);
				}
			}

			add_action('ry_check_ntp_time', [__CLASS__, 'check_ntp_time']);
			if( self::get_option('ntp_time_error', false) ) {
				add_action('admin_notices', [__CLASS__, 'ntp_time_error']);
			}

			add_filter('woocommerce_localisation_address_formats', [__CLASS__, 'add_address_format']);

			if( 'yes' == self::get_option('enabled_ecpay_gateway', 'no') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway.php');
			}
			if( 'yes' == self::get_option('enabled_ecpay_shipping', 'no') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping.php');
			}

			if( 'yes' == self::get_option('enabled_newebpay_gateway', 'no') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway.php');
			}
			if( 'yes' == self::get_option('enabled_newebpay_shipping', 'no') ) {
				include_once(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/newebpay-shipping.php');
			}

			if( 'no' == self::get_option('repay_action', 'no') ) {
				add_filter('woocommerce_my_account_my_orders_actions', [__CLASS__, 'remove_pay_action']);
			}
			if( 'no' == self::get_option('strength_password', 'yes') ) {
				if( ( !is_admin() || defined('DOING_AJAX') ) && !defined('DOING_CRON') ) {
					add_action('wp_enqueue_scripts', [__CLASS__, 'remove_strength_password_script'], 20);
				}
			}

			add_filter('woocommerce_form_field_hidden', [__CLASS__, 'form_field_hidden'], 20, 4);
			add_filter('woocommerce_form_field_hidden_empty', [__CLASS__, 'form_field_hidden_empty'], 20, 4);
			add_filter('woocommerce_form_field_hiddentext', [__CLASS__, 'form_field_hiddentext'], 20, 4);

			if( 'no' == self::get_option('show_country_select', 'no') ) {
				add_filter('woocommerce_billing_fields', [__CLASS__, 'hide_country_select'], 20);
				add_filter('woocommerce_shipping_fields', [__CLASS__, 'hide_country_select'], 20);
				add_filter('woocommerce_form_field_country_hidden', [__CLASS__, 'form_field_country_hidden'], 20, 4);
			}
			if( 'yes' == self::get_option('last_name_first', 'no') ) {
				add_filter('woocommerce_default_address_fields', [__CLASS__, 'last_name_first']);
			}
			if( 'yes' == self::get_option('address_zip_first', 'no') ) {
				add_filter('woocommerce_default_address_fields', [__CLASS__, 'address_zip_first']);
			}
		}
	}

	protected static function fixed_old_function() {
		if( !function_exists('wc_string_to_datetime') ) {
			function wc_string_to_datetime( $time_string ) {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $time_string, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				} else {
					$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $time_string ) ) ) );
				}
				$datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

				// Set local timezone or offset.
				if ( get_option( 'timezone_string' ) ) {
					$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
				} else {
					$datetime->set_utc_offset( wc_timezone_offset() );
				}

				return $datetime;
			}
		}
	}

	public static function unpay_title_notice($title, $order) {
		if( !$order->is_paid() ) {
			$title .= ' ' . __('(not paid)', 'ry-woocommerce-tools');
		}
		return $title;
	}

	public static function remove_unpay_title_notice($status) {
		remove_filter('woocommerce_order_get_payment_method_title', [__CLASS__, 'unpay_title_notice'], 10, 2);
		return $status;
	}

	public static function add_unpay_title_notice($status) {
		add_filter('woocommerce_order_get_payment_method_title', [__CLASS__, 'unpay_title_notice'], 10, 2);
		return $status;
	}

	public static function add_address_format($address_formats) {
		$address_formats['TW'] = "{postcode}\n{country} {state} {city}\n{address_1} {address_2}\n{company} {last_name} {first_name}";
		if( is_admin() ) {
			$address_formats['CVS'] = "{last_name}{first_name}\n{shipping_type}\n{cvs_store_name} ({cvs_store_ID})\n";
		} else {
			$address_formats['CVS'] = "{cvs_store_name} ({cvs_store_ID})\n{cvs_store_address}\n{cvs_store_telephone}\n{last_name} {first_name}\n"
				. '<p class="woocommerce-customer-details--phone">{phone}</p>';
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

	public static function address_zip_first($fields) {
		$fields['postcode']['priority'] = 50;
		$fields['state']['priority'] = 60;
		$fields['city']['priority'] = 70;
		$fields['address_1']['priority'] = 80;
		if( isset($fields['address_2']) ) {
			$fields['address_2']['priority'] = 90;
		}

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
			$required = ' <abbr class="required" title="' . esc_attr__('required', 'ry-woocommerce-tools') . '">*</abbr>';
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
		$custom_attributes = [];
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
			/* translators: %s: Name of this plugin */
			__('<strong>%s</strong> is inactive. It require WooCommerce version 3.0.0 or newer.', 'ry-woocommerce-tools'),
			__('RY WooCommerce Tools', 'ry-woocommerce-tools')
		);
		printf('<div class="error"><p>%s</p></div>', $message);
	}

	public static function check_ntp_time() {
		if( !function_exists('stream_socket_client') ) {
			wp_clear_scheduled_hook('ry_check_ntp_time');
			return;
		}

		$socket = stream_socket_client('udp://time.google.com:123', $errno, $errstr);
		if( $socket ) {
			fwrite($socket, chr(0x1B) . str_repeat(chr(0x00), 47));
			$response = fread($socket, 48);
			fclose($socket);
			if( empty($response) ) {
				return;
			}

			$data = @unpack('N12', $response);
			if( is_array($data) && isset($data[9]) ) {
				$timestamp = sprintf('%u', $data[9]) - 2208988800;
				$time_diff = current_time('timestamp', true) - $timestamp;
				self::update_option('ntp_time_error', abs($time_diff) > MINUTE_IN_SECONDS);

				return $time_diff;
			}
		}
	}

	public static function ntp_time_error() {
		printf('<div class="error is-dismissible"><p>%s</p></div>', __('Please check your server time setting. Server time is differs from Google Public NTP more than one minute.', 'ry-woocommerce-tools'));
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
		wp_unschedule_hook('ry_check_ntp_time');
	}

	// just for i18n use
	private static function bank_code_list() {
		_x('004', 'Bank code', 'ry-woocommerce-tools'); // 臺灣銀行
		_x('005', 'Bank code', 'ry-woocommerce-tools'); // 土地銀行
		_x('006', 'Bank code', 'ry-woocommerce-tools'); // 合庫商銀
		_x('007', 'Bank code', 'ry-woocommerce-tools'); // 第一銀行
		_x('008', 'Bank code', 'ry-woocommerce-tools'); // 華南銀行
		_x('009', 'Bank code', 'ry-woocommerce-tools'); // 彰化銀行
		_x('011', 'Bank code', 'ry-woocommerce-tools'); // 上海銀行
		_x('012', 'Bank code', 'ry-woocommerce-tools'); // 台北富邦
		_x('013', 'Bank code', 'ry-woocommerce-tools'); // 國泰世華
		_x('016', 'Bank code', 'ry-woocommerce-tools'); // 高雄銀行
		_x('017', 'Bank code', 'ry-woocommerce-tools'); // 兆豐銀行
		_x('018', 'Bank code', 'ry-woocommerce-tools'); // 農業金庫
		_x('021', 'Bank code', 'ry-woocommerce-tools'); // 花旗(台灣)銀行
		_x('022', 'Bank code', 'ry-woocommerce-tools'); // 美國銀行
		_x('025', 'Bank code', 'ry-woocommerce-tools'); // 首都銀行
		_x('039', 'Bank code', 'ry-woocommerce-tools'); // 澳商澳盛銀行
		_x('048', 'Bank code', 'ry-woocommerce-tools'); // 王道銀行
		_x('050', 'Bank code', 'ry-woocommerce-tools'); // 臺灣企銀
		_x('052', 'Bank code', 'ry-woocommerce-tools'); // 渣打商銀
		_x('053', 'Bank code', 'ry-woocommerce-tools'); // 台中銀行
		_x('054', 'Bank code', 'ry-woocommerce-tools'); // 京城商銀
		_x('072', 'Bank code', 'ry-woocommerce-tools'); // 德意志銀行
		_x('075', 'Bank code', 'ry-woocommerce-tools'); // 東亞銀行
		_x('081', 'Bank code', 'ry-woocommerce-tools'); // 匯豐(台灣)銀行
		_x('082', 'Bank code', 'ry-woocommerce-tools'); // 巴黎銀行
		_x('101', 'Bank code', 'ry-woocommerce-tools'); // 瑞興銀行
		_x('102', 'Bank code', 'ry-woocommerce-tools'); // 華泰銀行
		_x('103', 'Bank code', 'ry-woocommerce-tools'); // 臺灣新光商銀
		_x('108', 'Bank code', 'ry-woocommerce-tools'); // 陽信銀行
		_x('118', 'Bank code', 'ry-woocommerce-tools'); // 板信銀行
		_x('147', 'Bank code', 'ry-woocommerce-tools'); // 三信銀行
		_x('700', 'Bank code', 'ry-woocommerce-tools'); // 中華郵政
		_x('803', 'Bank code', 'ry-woocommerce-tools'); // 聯邦銀行
		_x('805', 'Bank code', 'ry-woocommerce-tools'); // 遠東銀行
		_x('806', 'Bank code', 'ry-woocommerce-tools'); // 元大銀行
		_x('807', 'Bank code', 'ry-woocommerce-tools'); // 永豐銀行
		_x('808', 'Bank code', 'ry-woocommerce-tools'); // 玉山銀行
		_x('809', 'Bank code', 'ry-woocommerce-tools'); // 凱基銀行
		_x('810', 'Bank code', 'ry-woocommerce-tools'); // 星展(台灣)銀行
		_x('812', 'Bank code', 'ry-woocommerce-tools'); // 台新銀行
		_x('815', 'Bank code', 'ry-woocommerce-tools'); // 日盛銀行
		_x('816', 'Bank code', 'ry-woocommerce-tools'); // 安泰銀行
		_x('822', 'Bank code', 'ry-woocommerce-tools'); // 中國信託

		return false;
	}
}
