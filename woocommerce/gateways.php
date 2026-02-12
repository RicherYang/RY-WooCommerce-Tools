<?php

final class RY_WT_WC_Gateways
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Gateways
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_order_get_payment_method_title', [$this, 'unpay_title_notice'], 10, 2);
        add_filter('woocommerce_pre_payment_complete', [$this, 'remove_unpay_title_notice']);
        add_filter('woocommerce_email_setup_locale', [$this, 'remove_unpay_title_notice']);
        add_filter('woocommerce_email_restore_locale', [$this, 'add_unpay_title_notice']);
    }

    public function add_unpay_title_notice($status)
    {
        remove_filter('ry_show_unpay_title_notice', '__return_false');

        return $status;
    }

    public function remove_unpay_title_notice($status)
    {
        add_filter('ry_show_unpay_title_notice', '__return_false');

        return $status;
    }

    public function unpay_title_notice($title, $order)
    {
        if (apply_filters('ry_show_unpay_title_notice', 'yes' === RY_WT::get_option('show_unpay_title', 'yes'))) {
            if (!$order->is_paid()) {
                $title .= ' ' . __('(not paid)', 'ry-woocommerce-tools');
            }
        }

        return $title;
    }
}
