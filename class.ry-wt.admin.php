<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_WT_admin {
	private static $initiated = false;
	
	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			add_filter('plugin_action_links_' . RY_WT_PLUGIN_BASENAME, [__CLASS__, 'plugin_action_links'], 10);

			add_filter('woocommerce_get_settings_pages', [__CLASS__, 'get_settings_page']);
			
			add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections'], 11);

			add_filter('ry_setting_section_tools', '__return_false');
			add_action('ry_setting_section_ouput_tools', [__CLASS__, 'output_tools']);
		}
	}

	public static function plugin_action_links($links) {
		return array_merge([
			'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=rytools') . '">' . __('Settings') . '</a>'
		], $links);
	}

	public static function get_settings_page($settings) {
		$settings[] = include(RY_WT_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-tools.php');

		return $settings;
	}

	public static function add_sections($sections) {
		$sections['tools'] = __('Tools', 'ry-woocommerce-tools');
		$sections['pro_info'] = __('Pro version', 'ry-woocommerce-tools');

		return $sections;
	}

	public static function output_tools() {
		global $hide_save_button;

		$hide_save_button = true;

		if( isset($_POST['ryt_check_time']) && $_POST['ryt_check_time'] == 'ryt_check_time' ) {
			$time_diff = RY_WT::check_ntp_time();

			if( RY_WT::get_option('ntp_time_error', false) ) {
				RY_WT::ntp_time_error();
			}
		}

		include RY_WT_PLUGIN_DIR . 'woocommerce/admin/view/html-setting-tools.php';
	}
}

RY_WT_admin::init();
