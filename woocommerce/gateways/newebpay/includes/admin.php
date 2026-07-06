<?php

defined('ABSPATH') or exit;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

final class RY_WT_WC_NewebPay_Gateway_Admin
{
    protected static ?self $_instance = null;

    public static function instance(): RY_WT_WC_NewebPay_Gateway_Admin
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
        $sections['newebpay_gateway'] = __('NewebPay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_gateway') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings/admin-settings.php';

            if (!CartCheckoutUtils::is_checkout_block_default() && !defined('RY_WTP_VERSION')) {
                $settings[0]['desc'] .= '<p>' . sprintf(
                    /* translators: %s: link to RY Tools (Pro) for WooCommerce */
                    __('Need %s to support block checkout.', 'ry-woocommerce-tools'),
                    '<a href="https://ry-plugin.com/ry-woocommerce-tools-pro">RY Tools (Pro) for WooCommerce</a>',
                ) . '</p>';
            }
        }

        return $settings;
    }

    public function check_option()
    {
        $api_info = RY_WT::get_option('newebpay_gateway_apiinfo', []);
        if (is_array($api_info) && isset($api_info['prefix'])) {
            if (!preg_match('/^[a-z0-9]{0,3}$/i', $api_info['prefix'])) {
                WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed, and maximum length is 3 characters.', 'ry-woocommerce-tools'));
                $api_info['prefix'] = '';
                RY_WT::update_option('newebpay_gateway_apiinfo', $api_info, false);
            }
        }
    }
}
