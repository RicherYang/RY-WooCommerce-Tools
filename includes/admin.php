<?php

final class RY_WT_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('admin_notices', [$this, 'need_woocommerce']);
        add_action('admin_notices', [$this, 'show_time_error']);

        add_filter('plugin_action_links_' . RY_WT_PLUGIN_BASENAME, [$this, 'plugin_action_links'], 10);
    }

    public function need_woocommerce(): void
    {
        if (!defined('WC_VERSION') || version_compare(WC_VERSION, RY_WT::$min_WooCommerce_version, '<')) {
            $message = sprintf(
                /* translators: %1$s: Name of this plugin %2$s: min require version */
                __('<strong>%1$s</strong> is inactive. It require WooCommerce version %2$s or newer.', 'ry-woocommerce-tools'),
                __('RY WooCommerce Tools', 'ry-woocommerce-tools'),
                RY_WT::$min_WooCommerce_version
            );
            printf('<div class="error"><p>%s</p></div>', wp_kses($message, ['strong' => []]));
        }
    }

    public function show_time_error(): void
    {
        if (RY_WT::get_option('ntp_time_error', false)) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Please check your server time setting. Server time is differs from NTP more than one minute.', 'ry-woocommerce-tools')
            );
        }
    }

    public function plugin_action_links($links)
    {
        return array_merge([
            'settings' => '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=rytools')) . '">' . __('Settings') . '</a>'
        ], $links);
    }

}
