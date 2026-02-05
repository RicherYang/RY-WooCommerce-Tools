<?php

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

if (class_exists('RY_WT_WC_Admin_Settings', false)) {
    if (!has_action('woocommerce_sections_rytools')) {
        return new RY_WT_WC_Admin_Settings();
    }
}

class RY_WT_WC_Admin_Settings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id = 'rytools';
        $this->label = __('RY Tools', 'ry-woocommerce-tools');

        parent::__construct();
    }

    public function get_sections()
    {
        $sections = [
            '' => __('Base options', 'ry-woocommerce-tools'),
        ];

        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }

    public function output()
    {
        global $current_section, $hide_save_button;

        if ('pro_info' === $current_section) {
            $hide_save_button = true;
            $this->output_pro_info();
        } else {
            if (apply_filters('ry_setting_section_' . $current_section, true)) {
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::output_fields($settings);
            } else {
                do_action('ry_setting_section_ouput_' . $current_section);
            }
        }
    }

    public function save()
    {
        global $current_section;

        if (apply_filters('ry_setting_section_' . $current_section, true)) {
            $settings = $this->get_settings($current_section);
            WC_Admin_Settings::save_fields($settings);
        }

        if ($current_section == '') {
            if ('yes' === RY_WT::get_option('enabled_newebpay_gateway', 'no')) {
                if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
                    WC_Admin_Settings::add_error(__('NewebPay gateway required PHP function `openssl_encrypt` and `openssl_decrypt`.', 'ry-woocommerce-tools'));
                    RY_WT::update_option('enabled_newebpay_gateway', 'no');
                }
            }

            if ('yes' === RY_WT::get_option('enabled_newebpay_shipping', 'no')) {
                if ('no' === RY_WT::get_option('enabled_newebpay_gateway', 'no')) {
                    WC_Admin_Settings::add_error(__('NewebPay shipping method need enable NewebPay gateway.', 'ry-woocommerce-tools'));
                    RY_WT::update_option('enabled_newebpay_shipping', 'no');
                }
            }

            if ('yes' === RY_WT::get_option('enabled_smilepay_gateway', 'no')) {
                if (!function_exists('simplexml_load_string')) {
                    WC_Admin_Settings::add_error(__('SmilePay gateway method need PHP function `simplexml_load_string`.', 'ry-woocommerce-tools'));
                    RY_WT::update_option('enabled_smilepay_gateway', 'no');
                }
            }

            if ('yes' === RY_WT::get_option('enabled_smilepay_shipping', 'no')) {
                if ('no' === RY_WT::get_option('enabled_smilepay_gateway', 'no')) {
                    WC_Admin_Settings::add_error(__('SmilePay shipping method need enable SmilePay gateway.', 'ry-woocommerce-tools'));
                    RY_WT::update_option('enabled_smilepay_shipping', 'no');
                }
            }
        } else {
            do_action('woocommerce_update_options_' . $this->id . '_' . $current_section);
        }
    }

    public function get_settings($current_section = '')
    {
        $checkout_with_block = CartCheckoutUtils::is_checkout_block_default();

        $settings = [];
        if ($current_section == '') {
            $settings = [
                [
                    'title' => __('ECPay support', 'ry-woocommerce-tools'),
                    'type' => 'title',
                    'id' => 'ecpay_support',
                ],
                [
                    'title' => __('Gateway method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable ECPay gateway method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with ECPay API, need enable secure checkout.', 'ry-woocommerce-tools'))
                        /* translators: %s: link to RY Tools (Pro) for WooCommerce */
                        . ($checkout_with_block || defined('RY_WTP_VERSION') ? '' : '<br>' . sprintf(__('Need %s to support block checkout.', 'ry-woocommerce-tools'), '<a href="https://ry-plugin.com/ry-woocommerce-tools-pro">RY Tools (Pro) for WooCommerce</a>')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_ecpay_gateway',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'title' => __('Shipping method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable ECPay shipping method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with ECPay API, need enable secure checkout.', 'ry-woocommerce-tools')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_ecpay_shipping',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'ecpay_support',
                ],

                [
                    'title' => __('NewebPay support', 'ry-woocommerce-tools'),
                    'type' => 'title',
                    'id' => 'newebpay_support',
                ],
                [
                    'title' => __('Gateway method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable NewebPay gateway method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with NewebPay API, need enable secure checkout.', 'ry-woocommerce-tools'))
                        /* translators: %s: link to RY Tools (Pro) for WooCommerce */
                        . ($checkout_with_block || defined('RY_WTP_VERSION') ? '' : '<br>' . sprintf(__('Need %s to support block checkout.', 'ry-woocommerce-tools'), '<a href="https://ry-plugin.com/ry-woocommerce-tools-pro">RY Tools (Pro) for WooCommerce</a>')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_newebpay_gateway',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'title' => __('Shipping method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable NewebPay shipping method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with NewebPay API, need enable secure checkout.', 'ry-woocommerce-tools')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_newebpay_shipping',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'newebpay_support',
                ],

                [
                    'title' => __('SmilePay support', 'ry-woocommerce-tools'),
                    'type' => 'title',
                    'id' => 'smilepay_support',
                ],
                [
                    'title' => __('Gateway method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable SmilePay gateway method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with SmilePay API, need enable secure checkout.', 'ry-woocommerce-tools'))
                        /* translators: %s: link to RY Tools (Pro) for WooCommerce */
                        . ($checkout_with_block || defined('RY_WTP_VERSION') ? '' : '<br>' . sprintf(__('Need %s to support block checkout.', 'ry-woocommerce-tools'), '<a href="https://ry-plugin.com/ry-woocommerce-tools-pro">RY Tools (Pro) for WooCommerce</a>')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_smilepay_gateway',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'title' => __('Shipping method', 'ry-woocommerce-tools'),
                    'desc' => __('Enable SmilePay shipping method', 'ry-woocommerce-tools')
                        . (wc_checkout_is_https() ? '' : '<br>' . __('For correct link with SmilePay API, need enable secure checkout.', 'ry-woocommerce-tools')),
                    'id' => RY_WT::OPTION_PREFIX . 'enabled_smilepay_shipping',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'smilepay_support',
                ],
                [
                    'title' => __('General options', 'ry-woocommerce-tools'),
                    'type' => 'title',
                    'id' => 'general_options',
                ],
                [
                    'title' => __('Repay action', 'ry-woocommerce-tools'),
                    'desc' => __('Show pay link at account orders page.', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'repay_action',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ],
                [
                    'title' => __('Strength password', 'ry-woocommerce-tools'),
                    'desc' => __('Enable the strength password check.', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'strength_password',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ],
                [
                    'title' => __('Show not paid info at order detail', 'ry-woocommerce-tools'),
                    'desc' => __('Show not paid info at order detail payment method info.', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'show_unpay_title',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'general_options',
                ],
                [
                    'title' => __('Address options', 'ry-woocommerce-tools'),
                    'type' => 'title',
                    'id' => 'checkout_page_options',
                ],
                [
                    'title' => __('Show Country', 'ry-woocommerce-tools'),
                    'desc' => __('Show Country select item', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'show_country_select',
                    'type' => 'checkbox',
                    'default' => 'yes',
                ],
                [
                    'title' => __('Last name first', 'ry-woocommerce-tools'),
                    'desc' => __('Show Last name before first name input item', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'last_name_first',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'title' => __('Address zip first', 'ry-woocommerce-tools'),
                    'desc' => __('Show address input item in zip state address', 'ry-woocommerce-tools'),
                    'id' => RY_WT::OPTION_PREFIX . 'address_zip_first',
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                [
                    'type' => 'sectionend',
                    'id' => 'checkout_page_options',
                ],
            ];

            if ($checkout_with_block) {
                $setting_idx = array_search(RY_WT::OPTION_PREFIX . 'show_country_select', array_column($settings, 'id'));
                unset($settings[$setting_idx]);
                $settings = array_values($settings);
            }
        }

        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section, $checkout_with_block);
    }

    public function output_pro_info()
    {
        include RY_WT_PLUGIN_DIR . 'woocommerce/admin/settings/html/pro-info.php';
    }
}

if (!has_action('woocommerce_sections_rytools')) {
    return new RY_WT_WC_Admin_Settings();
}
