<?php

namespace RY\General\V20260810;

defined('ABSPATH') or exit;

final class Utils
{
    public static function the_action_form(string $page, string $action, string $submit_text, array $hidden_value = []): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        foreach ($hidden_value as $name => $value) {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        }
        self::the_action_form_button($page, $action, $submit_text);
        echo '</form>';
    }

    public static function the_action_form_button(string $page, string $action, string $submit_text, string $type = 'submit', string $class = ''): void
    {
        $args = self::get_action_args($page, $action);
        foreach ($args as $name => $value) {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        }

        echo '<button type="' . esc_attr($type) . '" name="submit" id="ry-' . esc_attr($action) . '" class="button ' . esc_attr($class) . '" value="' . esc_attr($submit_text) . '">' . esc_html($submit_text) . '</button>';
    }

    public static function the_action_link(string $page, string $action, array $add_args = []): string
    {
        $add_args = array_merge($add_args, self::get_action_args($page, $action));

        return add_query_arg($add_args, admin_url('admin-post.php'));
    }

    private static function get_action_args(string $page, string $action): array
    {
        return [
            'action' => 'ry-' . $page,
            '_wpnonce' => wp_create_nonce('ry-' . $page),

            'ry-action' => $action,
            '_ajax_nonce' => wp_create_nonce($action),
        ];
    }

    public static function string_to_bool(string|bool|null $string): bool
    {
        $string = $string ?? '';
        return is_bool($string) ? $string : ('yes' === strtolower($string) || 'true' === strtolower($string) || '1' === $string || 1 === $string);
    }

    public static function bool_to_string(bool|string|null $bool): string
    {
        if (!is_bool($bool)) {
            $bool = self::string_to_bool($bool);
        }
        return $bool ? 'yes' : 'no';
    }
}
