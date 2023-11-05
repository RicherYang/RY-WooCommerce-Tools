<?php

final class RY_WT_WC_Account
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_Account
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        if ('no' === RY_WT::get_option('repay_action', 'yes')) {
            add_filter('woocommerce_my_account_my_orders_actions', [$this, 'remove_pay_action']);
        }

        if ('no' === RY_WT::get_option('strength_password', 'yes')) {
            if ((!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON')) {
                add_action('wp_enqueue_scripts', [$this, 'remove_strength_password_script'], 20);
            }
        }

        if (apply_filters('ry_show_unpay_title_notice', 'yes' === RY_WT::get_option('show_unpay_title', 'yes'))) {
            $this->add_unpay_title_notice(true);
            add_filter('woocommerce_email_setup_locale', [$this, 'remove_unpay_title_notice']);
            add_filter('woocommerce_email_restore_locale', [$this, 'add_unpay_title_notice']);
        }
    }

    public function remove_pay_action($actions)
    {
        unset($actions['pay']);

        return $actions;
    }

    public function remove_strength_password_script()
    {
        wp_dequeue_script('wc-password-strength-meter');
    }

    public function add_unpay_title_notice($status)
    {
        add_filter('woocommerce_order_get_payment_method_title', [$this, 'unpay_title_notice'], 10, 2);

        return $status;
    }

    public function remove_unpay_title_notice($status)
    {
        remove_filter('woocommerce_order_get_payment_method_title', [$this, 'unpay_title_notice'], 10, 2);

        return $status;
    }

    public function unpay_title_notice($title, $order)
    {
        if (!$order->is_paid()) {
            $title .= ' ' . __('(not paid)', 'ry-woocommerce-tools');
        }

        return $title;
    }
}
