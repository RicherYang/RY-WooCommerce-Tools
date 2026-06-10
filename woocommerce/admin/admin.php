<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_Admin
{
    protected static ?self $_instance = null;

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

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/product.php';

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
            $difftime = RY_WT_Cron::check_ntp_time();
            if ($difftime === -1) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p><strong>%s</strong></p></div>',
                    esc_html__('Check server time failed.', 'ry-woocommerce-tools')
                );
            } else {
                printf(
                    '<div class="notice notice-' . ($difftime > MINUTE_IN_SECONDS ? 'info' : 'success') . ' is-dismissible"><p><strong>%s</strong></p></div>',
                    esc_html(sprintf(
                        /* translators: %d: differ time (second) */
                        _n('Check server time success. Difference: %d second', 'Check server time success. Difference: %d seconds', $difftime, 'ry-woocommerce-tools'),
                        $difftime
                    ))
                );
            }
        }

        include RY_WT_PLUGIN_DIR . 'woocommerce/admin/settings/html/tools.php';
    }
}
