<?php

namespace RY\General\V20260724;

defined('ABSPATH') or exit;

abstract class AbstractLinkServer
{
    protected string $api_url = 'https://ry-plugin.com/wp-json/ry/v2/';

    protected string $plugin_slug;

    abstract protected function get_base_info();

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
