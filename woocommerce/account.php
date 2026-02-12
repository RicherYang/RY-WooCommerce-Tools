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
}
