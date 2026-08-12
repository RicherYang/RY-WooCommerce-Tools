<?php

namespace RY\General\V20260810\Page;

defined('ABSPATH') or exit;

use RY\General\V20260810\AbstractAdminPage;
use RY\General\V20260810\Utils;

final class Option extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        if (!has_action('admin_post_ry-general-option')) {
            add_filter('ry-plugin/menu_list', [__CLASS__, 'add_menu'], 999);
            add_action('admin_post_ry-general-option', [__CLASS__, 'admin_action']);
        }
    }

    public static function add_menu(array $menu_list): array
    {
        $menu_list[] = [
            'name' => __('Options', 'ry-woocommerce-tools'),
            'slug' => 'ry-options',
            'capability' => 'manage_options',
            'function' => [__CLASS__, 'pre_show_page'],
        ];

        return $menu_list;
    }

    protected function do_init(): void {}

    public function output_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading">' . esc_html__('Options', 'ry-woocommerce-tools') . '</h1>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        include __DIR__ . '/html/options.php';
        Utils::the_action_form_button('general-option', 'save-option', __('Save Changes', 'ry-woocommerce-tools'), 'submit', 'button-primary');
        echo '</form>';

        echo '</div>';
    }

    protected function do_admin_action(string $action, string $real_action): void
    {
        if ('ry-general-option' !== $action) {
            return;
        }

        if ($real_action !== '' && is_callable([$this, $real_action])) {
            $this->$real_action();
        }

        wp_safe_redirect(admin_url('admin.php?page=ry-options'));
        exit;
    }

    private function save_option(): void
    {
        check_ajax_referer('save-option', '_ajax_nonce');

        $tracking = isset($_POST['tracking']);
        update_option('RY_General_tracking', Utils::bool_to_string($tracking), false);
        if ($tracking) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_usage_tracking', [], '', true);
        } else {
            as_unschedule_all_actions('RY_GENERAL_usage_tracking');
        }
        $this->add_notice('success', __('Settings saved.', 'ry-woocommerce-tools'));
    }
}
