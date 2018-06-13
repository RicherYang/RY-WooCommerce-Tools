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

		if( version_compare($now_version, '0.0.19', '<' ) ) {
			RY_WT::update_option('version', '0.0.19');
		}
	}
}
