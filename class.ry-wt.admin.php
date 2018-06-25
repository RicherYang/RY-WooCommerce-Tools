<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

final class RY_WT_admin {
	private static $initiated = false;
	
	public static function init() {
		if( !self::$initiated ) {
			self::$initiated = true;

			add_filter('plugin_action_links_' . RY_WT_PLUGIN_BASENAME, array(__CLASS__, 'plugin_action_links'), 10);

			add_filter('woocommerce_get_settings_pages', array(__CLASS__, 'get_settings_page'));
		}
	}

	public static function plugin_action_links($links) {
		$action_links = array(
			'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=rytools') . '">' . __('Settings') . '</a>'
		);

		return array_merge($action_links, $links);
	}

	public static function get_settings_page($settings) {
		$settings[] = include(RY_WT_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-tools.php');

		return $settings;
	}
}
