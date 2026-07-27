<?php

namespace RY\General\V20260727;

defined('ABSPATH') or exit;

final class Utils
{
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
