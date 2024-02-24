<?php

final class RY_WT_WC_NewebPay_Shipping_Admin
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_NewebPay_Shipping_Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }
        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box'], 10, 2);

        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections']);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_newebpay_shipping', [$this, 'check_option']);

        add_action('add_meta_boxes', ['RY_NewebPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);
    }

    public function add_meta_box($post_type, $data_object)
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/meta-box.php';
        RY_NewebPay_Shipping_Meta_Box::add_meta_box($post_type, $data_object);
    }

    public function add_sections($sections)
    {
        $sections['newebpay_shipping'] = __('NewebPay shipping options', 'ry-woocommerce-tools');

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_shipping') {
            $settings = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/newebpay/includes/settings/admin-settings.php';

            if ('billing_only' === get_option('woocommerce_ship_to_destination')) {
                $setting_idx = array_search(RY_WT::OPTION_PREFIX . 'newebpay_shipping', array_column($settings, 'id'));
                $settings[$setting_idx]['desc'] .= '<br>' . __('Cvs only can enable with shipping destination not force to billing address.', 'ry-woocommerce-tools');
            }
        }

        return $settings;
    }

    public function check_option() {}
}
