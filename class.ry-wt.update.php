<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_WT_update {
	public static function update() {
		$now_version = RY_WT::get_option('version');

		if( $now_version === FALSE ) {
			$now_version = '0.0.0';
		}
		if( $now_version == RY_WT_VERSION ) {
			return;
		}

		if( version_compare($now_version, '0.0.6', '<' ) ) {
			RY_WT::update_option('version', '0.0.6');
		}

		if( version_compare($now_version, '0.0.7', '<' ) ) {
			if( !wp_next_scheduled('ry_check_ntp_time') ) {
				RY_WT::update_option('ntp_time_error', false);
				wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
			}
			RY_WT::update_option('version', '0.0.7');
		}

		if( version_compare($now_version, '0.0.17', '<' ) ) {
			RY_WT::update_option('version', '0.0.17');
		}

		if( version_compare($now_version, '0.0.18', '<' ) ) {
			RY_WT::update_option('last_name_first', RY_WT::get_option('name_merged'));
			RY_WT::delete_option('one_row_address');
			RY_WT::delete_option('name_merged');
			RY_WT::update_option('version', '0.0.18');
		}

		if( version_compare($now_version, '0.0.22', '<' ) ) {
			RY_WT::update_option('version', '0.0.22');
		}

		if( version_compare($now_version, '0.0.23', '<' ) ) {
			$orders = wc_get_orders(array(
				'limit' => -1
			));
			foreach( $orders as $order ) {
				$do_save = false;
				switch( $order->get_payment_method() ) {
					case 'ry_ecpay_atm':
						$meta_key = '_ecpay_atm_ExpireDate';
						break;
					case 'ry_ecpay_barcode':
						$meta_key = '_ecpay_barcode_ExpireDate';
						break;
					case 'ry_ecpay_cvs':
						$meta_key = '_ecpay_cvs_ExpireDate';
						break;
					default:
						$meta_key = '';
						break;
				}

				if( !empty($meta_key) ) {
					$expireDate = $order->get_meta($meta_key);
					if( !empty($expireDate) && strpos($expireDate, 'T') === FALSE ) {
						$time = new DateTime($expireDate, new DateTimeZone('Asia/Taipei'));
						$order->update_meta_data($meta_key, $time->format(DATE_ATOM));
						$do_save = true;
					}
				}

				$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
				if( is_array($cvs_info_list) ) {
					foreach( $cvs_info_list as $key => $item ) {
						if( strpos($item['edit'], 'T') === FALSE ) {
							$time = new DateTime($item['edit'], new DateTimeZone('Asia/Taipei'));
							$cvs_info_list[$key]['edit'] = $time->format(DATE_ATOM);
							$do_save = true;
						}
						if( strpos($item['create'], 'T') === FALSE ) {
							$time = new DateTime($item['create'], new DateTimeZone('Asia/Taipei'));
							$cvs_info_list[$key]['create'] = $time->format(DATE_ATOM);
							$do_save = true;
						}
						$order->update_meta_data('_shipping_cvs_info', $cvs_info_list);
					}
				}
				$order->save_meta_data();
			}

			RY_WT::update_option('version', '0.0.23');
		}
	}
}
