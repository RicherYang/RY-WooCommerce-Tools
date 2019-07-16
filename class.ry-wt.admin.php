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
			add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 11, 2);
			add_action('woocommerce_admin_field_pro_version_info', [__CLASS__, 'pro_version_info_setting']);
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
		$sections['pro_license'] = __('Pro version', 'ry-woocommerce-tools');
		return $sections;
	}

	public static function add_setting($settings, $current_section) {
		if( $current_section == 'pro_license' ) {
			$settings = include(RY_WT_PLUGIN_DIR . 'woocommerce/settings/settings-ry-tools-pro.php');
		}
		return $settings;
	}

	public static function pro_version_info_setting() {
		$GLOBALS['hide_save_button'] = true;
		?>
		<tr>
			<td>
				專業版已經上線，提供更多方便店家管理賣場的功能。<br>
				更完整的說明請至 <a href="https://richer.tw/ry-woocommerce-tools-pro/" target="_blank">RY WooCommerce Tools Pro</a> 觀看。
				提供的功能計有
				<ol>
					<li>前台的訂單詳細資訊頁面中顯示超商取貨的相關資訊。</li>
					<li>當運送方式為綠界超商取貨的時候，移除結帳畫面當中訂購人資訊中的地址欄位。</li>
					<li>綠界超商取貨 B2C 模式。</li>
					<li>綠界超商取貨離島運費加成。</li>
					<li>信用卡分期，將每一個期數設定為一個付款方式。</li>
				</ol>
			</td>
		</tr>
		<?php
	}
}

RY_WT_admin::init();
