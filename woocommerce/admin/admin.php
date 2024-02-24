<?php

final class RY_WT_WC_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-meta-box.php';

        add_filter('woocommerce_get_settings_pages', [$this, 'get_settings_page']);
        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 11);
        add_filter('ry_setting_section_tools', '__return_false');
        add_action('ry_setting_section_ouput_tools', [$this, 'output_tools']);
    }

    public function get_settings_page($settings)
    {
        $settings[] = include RY_WT_PLUGIN_DIR . 'woocommerce/admin/settings/ry-tools-settings.php';

        return $settings;
    }

    public function add_sections($sections)
    {
        $sections['tools'] = __('Tools', 'ry-woocommerce-tools');
        $sections['pro_info'] = __('Pro version', 'ry-woocommerce-tools');

        return $sections;
    }

    public function output_tools(): void
    {
        global $hide_save_button;

        $hide_save_button = true;

        if (isset($_POST['ryt_check_time']) && 'ryt_check_time' === $_POST['ryt_check_time']) {
            RY_WT_Cron::check_ntp_time();
            printf(
                '<div class="notice notice-success is-dismissible"><p><strong>%s</strong></p></div>',
                esc_html__('Check server time success.', 'ry-woocommerce-tools')
            );
        }

        include RY_WT_PLUGIN_DIR . 'woocommerce/admin/settings/html/tools.php';
    }
}
