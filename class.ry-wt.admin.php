<?php
final class RY_WT_admin
{
    protected static $_instance = null;

    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init()
    {
        add_filter('plugin_action_links_' . RY_WT_PLUGIN_BASENAME, [$this, 'plugin_action_links'], 10);

        add_filter('woocommerce_get_settings_pages', [$this, 'get_settings_page']);
        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 11);
        add_filter('ry_setting_section_tools', '__return_false');
        add_action('ry_setting_section_ouput_tools', [$this, 'output_tools']);
    }

    public function plugin_action_links($links)
    {
        return array_merge([
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=rytools') . '">' . __('Settings') . '</a>'
        ], $links);
    }

    public function get_settings_page($settings)
    {
        $settings[] = include RY_WT_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-tools.php';

        return $settings;
    }

    public function add_sections($sections)
    {
        $sections['tools'] = __('Tools', 'ry-woocommerce-tools');
        $sections['pro_info'] = __('Pro version', 'ry-woocommerce-tools');

        return $sections;
    }

    public function output_tools()
    {
        global $hide_save_button;

        $hide_save_button = true;

        if (isset($_POST['ryt_check_time']) && $_POST['ryt_check_time'] === 'ryt_check_time') {
            RY_WT::check_ntp_time();
            if (RY_WT::get_option('ntp_time_error', false)) {
                RY_WT::ntp_time_error();
            } else {
                printf('<div id="message" class="updated inline"><p><strong>%s</strong></p></div>', __('Check server time success.', 'ry-woocommerce-tools'));
            }
        }

        include RY_WT_PLUGIN_DIR . 'woocommerce/admin/view/html-setting-tools.php';
    }
}

RY_WT_admin::instance();
