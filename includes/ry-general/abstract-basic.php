<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Abstract_Basic', false)) {
    abstract class RY_Abstract_Basic
    {
        public const OPTION_PREFIX = '';

        public const PLUGIN_NAME = '';

        public static function get_option(string $option, mixed $default = false): mixed
        {
            return get_option(static::OPTION_PREFIX . $option, $default);
        }

        public static function update_option(string $option, mixed $value, ?bool $autoload = null)
        {
            return update_option(static::OPTION_PREFIX . $option, $value, $autoload);
        }

        public static function delete_option(string $option)
        {
            return delete_option(static::OPTION_PREFIX . $option);
        }

        public static function get_transient(string $transient)
        {
            return get_transient(static::OPTION_PREFIX . $transient);
        }

        public static function set_transient(string $transient, mixed $value, int $expiration = 0)
        {
            return set_transient(static::OPTION_PREFIX . $transient, $value, $expiration);
        }

        public static function delete_transient(string $transient)
        {
            return delete_transient(static::OPTION_PREFIX . $transient);
        }
    }
}
