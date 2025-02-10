<?php

final class RY_WT
{
    public const OPTION_PREFIX = 'RY_WT_';

    public const MIN_WC_VERSION = '8.0.0';

    public const MIN_PRO_TOOLS_VERSION = '3.5.3';

    protected static $_instance = null;

    public static function instance(): RY_WT
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        load_plugin_textdomain('ry-woocommerce-tools', false, plugin_basename(dirname(__DIR__)) . '/languages');

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'includes/update.php';
            RY_WT_Update::update();

            include_once RY_WT_PLUGIN_DIR . 'includes/admin.php';
            RY_WT_Admin::instance();
        }

        include_once RY_WT_PLUGIN_DIR . 'includes/cron.php';
        RY_WT_Cron::add_action();

        add_action('woocommerce_init', [$this, 'do_woo_init']);
    }

    public function do_woo_init(): void
    {
        if (version_compare(WC_VERSION, self::MIN_WC_VERSION, '<')) {
            return;
        }

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-api.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-model.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-shipping-model.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-wc-payment-gateway.php';
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/abstracts/abstract-wc-shipping-method.php';

        include_once RY_WT_PLUGIN_DIR . 'woocommerce/account.php';
        RY_WT_WC_Account::instance();
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/countries.php';
        RY_WT_WC_Countries::instance();
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/functions.php';

        if (is_admin()) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/admin.php';
            RY_WT_WC_Admin::instance();
        } else {
            add_action('wp_enqueue_scripts', [$this, 'load_scripts']);
        }

        if ('yes' === self::get_option('enabled_ecpay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/gateway.php';
            RY_WT_WC_ECPay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_ecpay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/shipping.php';
            RY_WT_WC_ECPay_Shipping::instance();
        }

        if ('yes' === self::get_option('enabled_newebpay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/gateway.php';
            RY_WT_WC_NewebPay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_newebpay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/shipping.php';
            RY_WT_WC_NewebPay_Shipping::instance();
        }

        if ('yes' === self::get_option('enabled_smilepay_gateway', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/gateways/smilepay/gateway.php';
            RY_WT_WC_SmilePay_Gateway::instance();
        }
        if ('yes' === self::get_option('enabled_smilepay_shipping', 'no')) {
            include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/smilepay/shipping.php';
            RY_WT_WC_SmilePay_Shipping::instance();
        }

        do_action('ry_woo_tools_loaded');
    }

    public function load_scripts()
    {
        $asset_info = include RY_WT_PLUGIN_DIR . 'assets/ry-checkout.asset.php';
        wp_register_script('ry-checkout', RY_WT_PLUGIN_URL . 'assets/ry-checkout.js', $asset_info['dependencies'], $asset_info['version'], true);

        if (is_checkout() || is_view_order_page() || is_order_received_page()) {
            $asset_info = include RY_WT_PLUGIN_DIR . 'assets/ry-payment.asset.php';
            wp_enqueue_style('ry-payment', RY_WT_PLUGIN_URL . 'assets/ry-payment.css', [], $asset_info['version']);
        }
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::OPTION_PREFIX . $option, $default);
    }

    public static function update_option($option, $value, $autoload = null)
    {
        return update_option(self::OPTION_PREFIX . $option, $value, $autoload);
    }

    public static function delete_option($option)
    {
        return delete_option(self::OPTION_PREFIX . $option);
    }

    public static function plugin_activation(): void
    {
        if (!wp_next_scheduled('ry_check_ntp_time')) {
            self::update_option('ntp_time_error', false);
            wp_schedule_event(time(), 'daily', 'ry_check_ntp_time');
        }
    }

    public static function plugin_deactivation(): void
    {
        wp_unschedule_hook('ry_check_ntp_time');
    }
}
