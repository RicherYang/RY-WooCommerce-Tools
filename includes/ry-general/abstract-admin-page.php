<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Abstract_Admin_Page', false)) {
    abstract class RY_Abstract_Admin_Page
    {
        protected static $_instance = null;

        abstract public function output_page(): void;

        abstract protected function do_init(): void;

        public static function instance()
        {
            if (null === static::$_instance) {
                static::$_instance = new static();
                static::$_instance->do_init();
            }

            return static::$_instance;
        }

        public static function pre_show_page(): void
        {
            static::instance()->output_page();
        }

        public static function admin_action(): void
        {
            $action = sanitize_text_field(wp_unslash($_REQUEST['action'] ?? ''));
            static::instance()->do_admin_action($action);
        }

        public function do_admin_action(string $action): void {}

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
}
