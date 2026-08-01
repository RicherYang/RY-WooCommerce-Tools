<?php

namespace RY\General\V20260801;

defined('ABSPATH') or exit;

class Logs
{
    public static function add_action(): void
    {
        add_action('RY_GENERAL_cleanup_logs', [__CLASS__, 'cleanup_logs']);
    }

    public static function cleanup_logs(): void
    {
        $delete_time = current_datetime();
        $delete_time = $delete_time->sub(new \DateInterval('P' . intval(apply_filters('ry-plugin/delete_time_days', 30)) . 'D'));
        $delete_time = $delete_time->getTimestamp();

        $delete_log = [];
        $file_dir = self::get_log_directory();
        foreach (new \FilesystemIterator($file_dir, \FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->isFile() && $file->isReadable()) {
                if ($file->getExtension() === 'log') {
                    if ($delete_time > $file->getMTime()) {
                        if (wp_delete_file($file->getPathname())) {
                            $delete_log[] = $file->getFilename();
                        }
                    }
                }
            }
        }

        if (count($delete_log)) {
            self::log('ry-logger', 'info', 'Deleted ' . count($delete_log) . ' log files', $delete_log);
        }
    }

    public static function get_log_directory(): string
    {
        $upload_dir = wp_upload_dir();
        $file_path = apply_filters('ry-plugin/log_directory', $upload_dir['basedir'] . '/ry-logs');
        $file_path = untrailingslashit($file_path);
        if (!is_dir($file_path)) {
            wp_mkdir_p($file_path);
        }
        if (!is_file($file_path . '/.htaccess')) {
            @file_put_contents($file_path . '/.htaccess', 'deny from all');
        }
        if (!is_file($file_path . '/index.html')) {
            @file_put_contents($file_path . '/index.html', '');
        }

        return realpath($file_path) . DIRECTORY_SEPARATOR;
    }

    public static function get_log_path(string $handle): string
    {
        $date_suffix = current_time('Y-m-d');
        $hash_suffix = wp_hash($handle . $date_suffix);
        $file_name = implode('-', [$handle, $date_suffix, $hash_suffix]) . '.log';
        $file_path = self::get_log_directory() . sanitize_file_name($file_name);
        if (!file_exists($file_path)) {
            @file_put_contents($file_path, '');
        }

        return realpath($file_path);
    }

    public static function log(string $handle, string $level, string $message, mixed $context = []): void
    {
        static $log_enabled = [];

        if (!isset($log_enabled[$handle])) {
            $log_enabled[$handle] = apply_filters('ry-plugin/log_enabled', true, $handle);
        }

        $level = strtoupper($level);
        if ($log_enabled[$handle] === false) {
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
