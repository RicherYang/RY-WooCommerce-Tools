<?php

namespace RY\General\V20260724;

defined('ABSPATH') or exit;

use RY\General\V20260724\ActionScheduler\View;

final class ActionScheduler
{
    private static ?self $_instance = null;

    public static function instance(): ActionScheduler
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('action_scheduler_pre_init', [$this, 'load']);
    }

    public function load(): void
    {
        if (is_admin()) {
            add_filter('action_scheduler_admin_view_class', [$this, 'change_adminview']);
        }
    }

    public function change_adminview(): string
    {
        return View::class;
    }
}
