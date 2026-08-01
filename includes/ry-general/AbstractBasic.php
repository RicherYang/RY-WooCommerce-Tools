<?php

namespace RY\General\V20260801;

defined('ABSPATH') or exit;

use RY\General\V20260801\ActionScheduler;
use RY\General\V20260801\Logs;
use RY\General\V20260801\Utils;

abstract class AbstractBasic
{
    public const PREFIX = '';

    public const PLUGIN_NAME = '';

    public function __construct()
    {
        include_once __DIR__ . '/../vendor/woocommerce/action-scheduler/action-scheduler.php';
        ActionScheduler::instance();

        if (!has_action('RY_GENERAL_usage_tracking')) {
            add_action('RY_GENERAL_cleanup_logs', [Logs::class, 'cleanup_logs']);

            add_action('action_scheduler_ensure_recurring_actions', [$this, 'register_recurring_actions']);
            if (method_exists(static::class, 'usage_tracking')) {
                add_action('RY_GENERAL_usage_tracking', [static::class, 'usage_tracking']);
            }
        }
    }

    abstract public static function plugin_activation(): void;

    abstract public static function plugin_deactivation(): void;

    public function register_recurring_actions(): void
    {
        as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_cleanup_logs', [], '', true);

        if (Utils::string_to_bool(get_option('RY_General_tracking', 'yes'))) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_usage_tracking', [], '', true);
        } else {
            as_unschedule_all_actions('RY_GENERAL_usage_tracking');
        }
    }

    public static function get_prefix_name(string $option): string
    {
        return static::PREFIX . $option;
    }

    public static function get_option(string $option, mixed $default = false): mixed
    {
        return get_option(static::PREFIX . $option, $default);
    }

    public static function update_option(string $option, mixed $value, ?bool $autoload = null)
    {
        return update_option(static::PREFIX . $option, $value, $autoload);
    }

    public static function delete_option(string $option)
    {
        return delete_option(static::PREFIX . $option);
    }

    public static function get_transient(string $transient)
    {
        return get_transient(static::PREFIX . $transient);
    }

    public static function set_transient(string $transient, mixed $value, int $expiration = 0)
    {
        return set_transient(static::PREFIX . $transient, $value, $expiration);
    }

    public static function delete_transient(string $transient)
    {
        return delete_transient(static::PREFIX . $transient);
    }
}
