<?php

namespace RY\General;

defined('ABSPATH') or exit;

class Logs
{
    protected static array $log_enabled = [];

    public static function set_log(bool $enabled, string $handle = ''): void
    {
        self::$log_enabled[$handle] = $enabled;
    }

    public static function get_log_path(string $handle): string
    {
        global $wp_filesystem;

        $log_path = WP_CONTENT_DIR . '/ry-logs';
        if (!is_dir($log_path)) {
            wp_mkdir_p($log_path);

            if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
                include_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            $wp_filesystem->put_contents($log_path . '/.htaccess', 'deny from all');
            $wp_filesystem->put_contents($log_path . '/index.html', '');
        }

        $log_name = [$handle, current_time('Y-m-d'), wp_hash($handle)];
        $log_path = trailingslashit($log_path) . sanitize_file_name(implode('-', $log_name) . '.log');

        if (!file_exists($log_path)) {
            if (!$wp_filesystem instanceof \WP_Filesystem_Base) {
                include_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            $wp_filesystem->put_contents($log_path, '');
        }

        return realpath($log_path);
    }

    public static function log(string $handle, string $level, string $message, mixed $context = []): void
    {
        $level = strtoupper($level);
        if (isset(self::$log_enabled[$handle]) && self::$log_enabled[$handle] === false) {
            if ($level !== 'ERROR') {
                return;
            }
        }

        $add_message = current_time('c') . ' ' . $level . ' ' . $message;
        if (!empty($context)) {
            $add_message .= ' **CONTEXT** ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(self::get_log_path($handle), $add_message . "\n", FILE_APPEND);
    }
}
