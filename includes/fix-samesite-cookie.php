<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_WT_Fix_cookie
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            self::fix_woocommerce_session();

            add_action('set_auth_cookie', [__CLASS__, 'set_wp_auth_cookie'], 10, 5);
            add_action('set_logged_in_cookie', [__CLASS__, 'set_wp_logged_in_cookie'], 10, 4);
            add_filter('send_auth_cookies', '__return_false');
        }
    }

    protected static function fix_woocommerce_session()
    {
        if (isset($_COOKIE['wp_woocommerce_session_' . COOKIEHASH])) {
            $secure = apply_filters('wc_session_use_secure_cookie', wc_site_is_https() && is_ssl());
            if ($secure) {
                $samesite = 'None';
            } else {
                $samesite = 'Lax';
            }
            setcookie('wp_woocommerce_session_' . COOKIEHASH, $_COOKIE['wp_woocommerce_session_' . COOKIEHASH], [
                'expires' => time() + intval(apply_filters('wc_session_expiration', 60 * 60 * 48)),
                'path' => COOKIEPATH ? COOKIEPATH : '/',
                'domain' => COOKIE_DOMAIN,
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $samesite
            ]);
        }
    }

    public static function set_wp_auth_cookie($auth_cookie, $expire, $expiration, $user_id, $scheme)
    {
        if ($scheme == 'secure_auth') {
            $auth_cookie_name = SECURE_AUTH_COOKIE;
            $secure = true;
            $samesite = 'None';
        } else {
            $auth_cookie_name = AUTH_COOKIE;
            $secure = false;
            $samesite = 'Lax';
        }

        setcookie($auth_cookie_name, $auth_cookie, [
            'expires' => $expire,
            'path' => PLUGINS_COOKIE_PATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $samesite
        ]);
        setcookie($auth_cookie_name, $auth_cookie, [
            'expires' => $expire,
            'path' => ADMIN_COOKIE_PATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $samesite
        ]);
    }

    public static function set_wp_logged_in_cookie($logged_in_cookie, $expire, $expiration, $user_id)
    {
        $secure = is_ssl();
        $secure_logged_in_cookie = $secure && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);
        $secure = apply_filters('secure_auth_cookie', $secure, $user_id);
        $secure_logged_in_cookie = apply_filters('secure_logged_in_cookie', $secure_logged_in_cookie, $user_id, $secure);
        if ($secure_logged_in_cookie) {
            $samesite = 'None';
        } else {
            $samesite = 'Lax';
        }

        setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, [
            'expires' => $expire,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => $secure_logged_in_cookie,
            'httponly' => true,
            'samesite' => $samesite
        ]);
        if (COOKIEPATH != SITECOOKIEPATH) {
            setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, [
                'expires' => $expire,
                'path' => SITECOOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => $secure_logged_in_cookie,
                'httponly' => true,
                'samesite' => $samesite
            ]);
        }
    }
}

if (version_compare(PHP_VERSION, '7.3', '>=')) {
    RY_WT_Fix_cookie::init();
}
