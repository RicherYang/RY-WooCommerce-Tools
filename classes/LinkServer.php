<?php

namespace RY\WooCommerce;

defined('ABSPATH') or exit;

use RY\General\V20260810\AbstractLinkServer;

final class LinkServer extends AbstractLinkServer
{
    private static ?self $_instance = null;

    protected string $plugin_slug = 'ry-woocommerce-tools';

    public static function instance(): LinkServer
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function get_base_info(): array
    {
        return [];
    }
}
