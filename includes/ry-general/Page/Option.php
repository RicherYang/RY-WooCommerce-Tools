<?php

namespace RY\General\V20260727\Page;

defined('ABSPATH') or exit;

use RY\General\V20260727\AbstractAdminPage;
use RY\General\V20260727\Utils;

final class Option extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        add_filter('ry-plugin/menu_list', [__CLASS__, 'add_menu'], 999);
        add_action('admin_post_ry-general-options', [__CLASS__, 'admin_action']);
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

        echo '<form method="post" action="admin-post.php">';
        echo '<input type="hidden" name="action" value="ry-general-options">';
        wp_nonce_field('ry-general-options');
        include __DIR__ . '/html/options.php';
        submit_button();
        echo '</form>';

        echo '</div>';
    }

    public function do_admin_action(string $action): void
    {
        if ('ry-general-options' !== $action) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ry-general-options')) {
            wp_die('Invalid nonce');
        }

        $tracking = Utils::string_to_bool($_POST['tracking'] ?? 'yes');
        update_option('RY_General_tracking', Utils::bool_to_string($tracking), false);
        if ($tracking) {
            as_schedule_recurring_action(time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, 'RY_GENERAL_usage_tracking', [], '', true);
        } else {
            as_unschedule_all_actions('RY_GENERAL_usage_tracking');
        }
        $this->add_notice('success', __('Settings saved.', 'ry-woocommerce-tools'));

        wp_safe_redirect(admin_url('admin.php?page=ry-options'));
        exit;
    }
}
