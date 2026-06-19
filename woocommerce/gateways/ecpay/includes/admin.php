<?php

defined('ABSPATH') or exit;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

final class RY_WT_WC_ECPay_Gateway_Admin
{
    protected static ?self $_instance = null;

    public static function instance(): RY_WT_WC_ECPay_Gateway_Admin
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
        add_action('woocommerce_update_options_rytools_ecpay_gateway', [$this, 'check_option']);
    }

    public function add_sections($sections)
    {
        $sections['ecpay_gateway'] = __('ECPay gateway options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'ecpay_gateway') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings/admin-settings.php';

            $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();
            if ($api_info['testmode']) {
                $setting_idx = array_search(RY_WT::OPTION_PREFIX . 'ecpay_gateway_apiinfo[MerchantID]', array_column($settings, 'id'));
                $settings[$setting_idx]['desc'] = '<p class="description">' . sprintf(
                    /* translators: %s: MerchantID */
                    __('Used MerchantID "%s"', 'ry-woocommerce-tools'),
                    $api_info['MerchantID'],
                ) . '</p>';
            }

            if (!CartCheckoutUtils::is_checkout_block_default() && !defined('RY_WTP_VERSION')) {
                $settings[0]['desc'] .= '<p>' . sprintf(
                    /* translators: %s: link to RY Tools (Pro) for WooCommerce */
                    __('Need %s to support block checkout.', 'ry-woocommerce-tools'),
                    '<a href="https://ry-plugin.com/ry-woocommerce-tools-pro">RY Tools (Pro) for WooCommerce</a>',
                ) . '</p>';
            }

            if (!defined('RY_WEI_VERSION') && !defined('RY_WSI_VERSION') && !defined('RY_WEZI_VERSION')) {
                $settings[0]['desc'] .= '<p>' . sprintf(
                    /* translators: %s: link to RY ECPay Invoice for WooCommerce */
                    __('If you need ECPay Invoice support, you can try %s', 'ry-woocommerce-tools'),
                    '<a href="https://ry-plugin.com/ry-woocommerce-ecpay-invoice">RY ECPay Invoice for WooCommerce</a>',
                ) . '</p>';
            }
        }

        return $settings;
    }

    public function check_option()
    {
        $api_info = RY_WT::get_option('ecpay_gateway_apiinfo', []);
        if (is_array($api_info) && isset($api_info['prefix'])) {
            if (!preg_match('/^[a-z0-9]{0,3}$/i', $api_info['prefix'])) {
                WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed, and maximum length is 3 characters.', 'ry-woocommerce-tools'));
                $api_info['prefix'] = '';
                RY_WT::update_option('ecpay_gateway_apiinfo', $api_info, false);
            }
        }
    }
}
