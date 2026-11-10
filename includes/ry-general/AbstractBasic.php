<?php

namespace RY\General\V20261107;

defined('ABSPATH') or exit;

abstract class AbstractBasic
{
    public const PREFIX = '';

    public const PLUGIN_NAME = '';

    public function __construct()
    {
        include_once __DIR__ . '/../vendor/woocommerce/action-scheduler/action-scheduler.php';
        ActionScheduler::instance();

        add_action('action_scheduler_ensure_recurring_actions', [$this, 'register_recurring_actions']);

        if (!has_action('RY_GENERAL_ntp_time')) {
            add_action('RY_GENERAL_ntp_time', [$this, 'check_ntp_time']);
        }

        if (!has_action('RY_GENERAL_cleanup_logs')) {
            add_action('RY_GENERAL_cleanup_logs', [Logs::class, 'cleanup_logs']);
        }

        if (!has_action('RY_GENERAL_usage_tracking')) {
            if (method_exists(static::class, 'usage_tracking')) {
                add_action('RY_GENERAL_usage_tracking', [static::class, 'usage_tracking']);
            }
        }
    }

    public function register_recurring_actions(): void
    {
        as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_cleanup_logs', [], '', true);

        if (function_exists('stream_socket_client')) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_ntp_time', [], '', true);
        }

        if (Utils::string_to_bool(get_option('RY_GENERAL_tracking', 'yes'))) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_usage_tracking', [], '', true);
        } else {
            as_unschedule_all_actions('RY_GENERAL_usage_tracking');
        }
    }

    public static function check_ntp_time()
    {
        $time_diff = -1;
        if (function_exists('stream_socket_client')) {
            $ntp_server_url = apply_filters('ry_ntp_server_url', 'udp://time.google.com:123');
            $socket = stream_socket_client($ntp_server_url, $errno, $errstr, 5);
            if ($socket) {
                fwrite($socket, chr(0x1B) . str_repeat(chr(0x00), 47)); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
                $response = fread($socket, 48); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
                fclose($socket); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                if (!empty($response)) {
                    $data = @unpack('N12', $response);
                    if (is_array($data) && isset($data[9])) {
                        $ntp_time = sprintf('%u', $data[9]) - 2208988800;

                        if ($ntp_time > 0) {
                            $time_diff = abs(current_time('timestamp', true) - $ntp_time);
                        }
                    }
                }
            }
        }

        update_option('RY_GENERAL_ntp_time_diff', $time_diff);
        return $time_diff;
    }

    public static function get_prefix_name(string $option): string
    {
        return static::PREFIX . $option;
    }

    public static function get_option(string $option, $default = false)
    {
        return get_option(static::PREFIX . $option, $default);
    }

    public static function update_option(string $option, $value = '', $autoload = null)
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

    public static function set_transient(string $transient, $value = '', $expiration = 0)
    {
        return set_transient(static::PREFIX . $transient, $value, $expiration);
    }

    public static function delete_transient(string $transient)
    {
        return delete_transient(static::PREFIX . $transient);
    }

    public static function plugin_activation(): void
    {
        if (function_exists('stream_socket_client')) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_ntp_time', [], '', true);
        }
    }

    public static function plugin_deactivation(): void
    {
        as_unschedule_all_actions('RY_GENERAL_ntp_time');
        as_unschedule_all_actions('RY_GENERAL_cleanup_logs');
        as_unschedule_all_actions('RY_GENERAL_usage_tracking');
    }
}
