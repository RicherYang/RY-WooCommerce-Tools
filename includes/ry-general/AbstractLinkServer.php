<?php

namespace RY\General\V20260727;

defined('ABSPATH') or exit;

abstract class AbstractLinkServer
{
    protected string $api_url = 'https://ry-plugin.com/wp-json/ry/v2/';

    protected string $plugin_slug;

    abstract protected function get_base_info();

    public function send_tracking()
    {
        @set_time_limit(30);

        $response = wp_remote_post($this->api_url . 'tracking', [
            'timeout' => 20,
            'httpversion' => '1.1',
            'user-agent' => 'WordPress; RY Plugin',
            'headers' => [
                'Content-Type' => 'application/json;charset=' . get_bloginfo('charset'),
            ],
            'body' => wp_json_encode([
                'domain' => get_option('siteurl'),
                'email' => apply_filters('ry-plugin/tracker_admin_email', get_option('admin_email')),
                'wp' => $this->get_wp_info(),
                'server' => $this->get_server_info(),
                'theme' => $this->get_theme_info(),
                'plugin' => $this->get_plugin_info(),
            ]),
        ]);

        return $this->decode_response($response);
    }

    private function get_wp_info()
    {
        $info = [];
        $info['version'] = get_bloginfo('version');
        $info['locale'] = get_locale();
        $info['debug'] = (defined('WP_DEBUG') && WP_DEBUG) ? 'Yes' : 'No';

        return $info;
    }

    private function get_server_info()
    {
        global $wpdb;

        $info = [];
        $info['php_version'] = PHP_VERSION;
        $info['db_version'] = $wpdb->get_var('SELECT VERSION()');

        return $info;
    }

    private function get_theme_info()
    {
        $theme = wp_get_theme();
        $info = [];
        $info['name'] = $theme->get('Name');
        $info['version'] = $theme->get('Version');
        $info['slug'] = $theme->get_stylesheet();
        $info['parent'] = $theme->parent() ? $theme->parent()->get('Name') : '';

        return $info;
    }

    private function get_plugin_info()
    {
        if (! function_exists('get_plugins')) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $info = [];
        foreach ($plugins as $plugin_file => $plugin) {
            if (is_plugin_active($plugin_file)) {
                $info[] = [
                    'name' => $plugin['Name'],
                    'version' => $plugin['Version'],
                    'slug' => str_contains($plugin_file, '/') ? dirname($plugin_file) : $plugin_file,
                ];
            }
        }

        return $info;
    }

    protected function decode_response($response)
    {
        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) != 200) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data)) {
            return false;
        }

        return $data;
    }
}
