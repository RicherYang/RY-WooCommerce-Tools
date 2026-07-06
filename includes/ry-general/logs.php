<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Logs', false)) {
    class RY_Logs
    {
        protected static array $log_enabled = [];

        public static function set_log(bool $enabled, string $handle = ''): void
        {
            self::$log_enabled[$handle] = $enabled;
        }

        public static function get_log_path(string $handle): string
        {
            $log_path = WP_CONTENT_DIR . '/ry-logs';
            if (!is_dir($log_path)) {
                include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
                include_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

                wp_mkdir_p($log_path);
                $filesystem = new WP_Filesystem_Direct(false);
                $filesystem->put_contents($log_path . '/.htaccess', 'deny from all');
                $filesystem->put_contents($log_path . '/index.html', '');
            }

            $log_name = [$handle, current_time('Y-m-d'), wp_hash($handle)];
            $log_path = trailingslashit($log_path) . sanitize_file_name(implode('-', $log_name) . '.log');

            if (!file_exists($log_path)) {
                @file_put_contents($log_path, '');
            }

            return realpath($log_path);
        }

        public static function log(string $handle, string $level, string $message, mixed $context = []): void
        {
            $level = strtoupper($level);
            if (!self::$log_enabled[$handle]) {
                if ($level !== 'ERROR') {
                    return;
                }
            }

            $add_message = current_time('c') . ' ' . strtoupper($level) . ' ' . $message;
            if (!empty($context)) {
                $add_message .= ' **CONTEXT** ' . json_encode($context, JSON_UNESCAPED_UNICODE);
            }
            @file_put_contents(self::get_log_path($handle), $add_message . "\n", FILE_APPEND);
        }
    }
}
