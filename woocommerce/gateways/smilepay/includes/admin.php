<?php

final class RY_WT_WC_SmilePay_Gateway_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_SmilePay_Gateway_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }
        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_newebpay_gateway', [$this, 'check_option']);
    }

    public function add_sections($sections)
    {
        $sections['smilepay_gateway'] = __('SmilePay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'smilepay_gateway') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/includes/settings/admin-settings.php';

            if (!defined('RY_WSI_VERSION')) {
                $settings[0]['desc'] = sprintf(
                    /* translators: %s: link to RY WooCommerce SmilePay Invoice */
                    __('<p>If you need SmilePay Invoice support, you can try %s</p>', 'ry-woocommerce-tools'),
                    '<a href="https://ry-plugin.com/ry-woocommerce-smilepay-invoice">RY WooCommerce SmilePay Invoice</a>'
                );
            }
        }

        return $settings;
    }

    public function check_option()
    {
        if (!preg_match('/^[a-z0-9]*$/i', RY_WT::get_option('newebpay_gateway_order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed', 'ry-woocommerce-tools'));
            RY_WT::update_option('newebpay_gateway_order_prefix', '');
        }
    }
}
