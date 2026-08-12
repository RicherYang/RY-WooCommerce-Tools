<?php

namespace RY\General\V20260810;

defined('ABSPATH') or exit;

abstract class AbstractAdminPage
{
    private static array $_instance = [];

    abstract public function output_page(): void;

    abstract protected function do_init(): void;

    public static function instance()
    {
        if (!isset(static::$_instance[static::class])) {
            static::$_instance[static::class] = new static();
            static::$_instance[static::class]->do_init();
        }

        return static::$_instance[static::class];
    }

    public static function set_page_load(string $hook_suffix): void
    {
        add_action('load-' . $hook_suffix, [static::class, 'process_admin_ui']);
    }

    public static function process_admin_ui(): void
    {
        static::instance();
    }

    public static function pre_show_page(): void
    {
        static::instance()->output_page();
    }

    public static function admin_action(): void
    {
        $action = wp_unslash($_REQUEST['action'] ?? '');
        if ($action === sanitize_key($action)) {
            check_ajax_referer($action, '_wpnonce');

            $real_action = wp_unslash($_REQUEST['ry-action'] ?? '');
            if ($real_action === sanitize_key($real_action)) {
                $real_action = str_replace('-', '_', $real_action);
            } else {
                $real_action = '';
            }
            static::instance()->do_admin_action($action, $real_action);
        }
    }

    protected function do_admin_action(string $action, string $real_action): void {}

    protected function add_notice(string $status, string $message): void
    {
        $notice = get_transient('ry-notice');
        if (!is_array($notice)) {
            $notice = [];
        }
        if (!isset($notice[$status])) {
            $notice[$status] = [];
        }
        $notice[$status][] = $message;

        set_transient('ry-notice', $notice);
    }
}
