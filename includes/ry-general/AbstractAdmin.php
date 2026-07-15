<?php

namespace RY\General;

defined('ABSPATH') or exit;

use RY\General\Page\Logs;

abstract class AbstractAdmin
{
    public string $main_slug;

    protected function do_init(): void
    {
        Logs::init_menu();

        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function admin_menu(): void
    {
        global $_parent_pages;

        $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjggMTI4Ij48cGF0aCBmaWxsPSJjdXJyZW50Q29sb3IiIGQ9Ik01Ny43NSA0LjljNS42IDMuMyA4IDguNCA4LjEgMTQuOC4xIDUuNi0xLjMgMTAuNi00LjQgMTUuMi0uNi44LTEuMiAxLjYtMiAyLjUgMS0uMiAxLjgtLjMgMi42LS40IDIuMi0uNCA0LjUtLjcgNi44LS40IDIuOC40IDQuMiAyLjMgNS4yIDQuNiAxLjggMy44IDMuNCA3LjUgNSAxMS40IDMuOSA5LjIgNy41IDE4LjUgMTAuNiAyOCAuMyAxIC42IDIuMSAxIDMuMy45LTEuMSAxLjItMi4zIDEuNy0zLjQgMi43LTYgNS4zLTEyLjEgNy45LTE4IDIuOS02LjYgNS45LTEzLjEgOS4yLTE5LjUuOC0xLjYgMS41LTMuMiAyLjctNC41IDEuOC0yLjEgNC4zLTIuMSA2LjgtMS45IDEuMy4xIDIuNS41IDMuOCAxLjEuOC40LjkuOC40IDEuNS0zLjMgNC4zLTYuMyA4LjYtOS4yIDEzLjItMy43IDUuNi03LjEgMTEuMy0xMC4zIDE3LjItMi45IDUuNC01LjcgMTEtOC4xIDE2LjctMS4zIDMuMi0xLjkgNi41LTIuMiA5LjktLjUgNC43LS42IDkuNC0uOCAxNC4xLS4yIDQuNS0uNCA4LjgtLjQgMTMuMyAwIC4xIDAgLjIgMCAuNCAwIDIuMS0uMSAyLjQtMi4xIDIuNy0yLjIuMy00LjYuNS03IC44LS45LjEtMS40LS4yLTEuMy0xLjIuMy00LjkuNS05LjguNy0xNC44IDAtLjEgMC0uMiAwLS40LS4xLTUuNS41LTExLjIuMi0xNi43LS4xLTItLjItNC4xLTEtNi4yLTEuNi42LTMuMiAxLjItNC43IDEuOC0zIDEuMi02IDItOS4yIDIuMS0zIC4xLTUuNS0xLjMtNy45LTMuMy02LjEtNS4zLTEwLjUtMTIuMS0xNC45LTE4LjgtMy42LTUuMy02LjgtMTAuOS0xMC0xNi42LTEuMy0yLjItMi43LTQuNS00LTYuOC0uMy0uNS0uNi0xLS42LTEuOCAxLjEtLjEgMi4yLS4zIDMuMy0uNCA1LjItLjUgMTAuMS0xLjYgMTQuNC00LjkgMi43LTIuMSA0LjctNC44IDUuOC04LjIgMS40LTQuNSAxLjYtOSAuNi0xMy42LTEuNS02LjUtNS43LTEwLjMtMTIuMS0xMS42LTQuMS0uOC04LjEtLjYtMTIuMi0uMi0xLjEuMS0xLjUuNS0xLjYgMS44LS4yIDMuOCAwIDcuNC0uMiAxMS4xLS4yIDIuOC0uMSA1LjUtLjIgOC4zLS4zIDUuOC0uMyAxMS42LS42IDE3LjQtLjEgMi42IDAgNS4xLS4yIDcuOC0uMyA1LjktLjMgMTItLjUgMTcuOS0uMSAyLjQgMCA0LjgtLjIgNy4zLS40IDQgMCA3LjktLjUgMTEuOCAwIC40IDAgLjktLjcuOS0yLjguMy01LjQuOC04LjIgMS0xIDAtMS40LS4zLTEuMy0xLjQuMy00LjcuNS05LjMuNy0xNCAuMS0yLjEgMC00LjIuMi02LjQuMy0zLjYuMS03LjIuMy0xMC45LjItMy41IDAtNi45LjMtMTAuNC4zLTMuOCAwLTcuNC4yLTExLjEuNS04LjIuMS0xNi4yLjItMjQuMiAwLTMuMy0xLjEtNC41LTQuNC01LTIuNy0uNC01LjIuMy03LjkuNC0uNy0uOS0uNC0yLjEtLjYtMy4yLS4xLS41LjQtLjcuOC0uNyAzLjEtLjUgNi0xIDkuMS0xLjUgMy4zLS41IDYuNS0xIDkuOS0xLjQgMS45LS4yIDMuOS0uNCA1LjktLjYgNC0uMyA3LjgtLjQgMTEuNy0uMiA0LjcuMiA5LjMuOSAxMy41IDMgLjYuMyAxLjEuNiAxLjcgMU01NS4zNSA2OGMyLjUgMy4zIDQuOCA2LjYgNy43IDkuNSAyLjMgMi40IDQuNSA0LjggNy42IDYuMyAzLjIgMS42IDYuMyAxLjMgOS41IDAtLjUtMS42LTEtMy4xLTEuNS00LjUtMi01LjYtNC4xLTExLjItNi41LTE2LjYtMi4yLTUuMS00LjUtMTAuMi03LTE1LjItMS0yLjMtMi43LTQuMS01LjMtNC42LTItLjQtNC4yLS4yLTYuMy4xLTEuMS4xLTIuMy4yLTMuMy44LTEuNi45LTMuNCAxLjYtNS4yIDIuMi0zIDEtMi45IDEtMS4zIDMuNyAzLjggNi4xIDcuNSAxMi4zIDExLjcgMTguM1oiLz48L3N2Zz4=';

        $menu_list = apply_filters('ry-plugin/menu_list', []);
        $this->main_slug = $menu_list[0]['slug'];

        add_action('all_admin_notices', [$this, 'show_not_activated']);
        if (!isset($_parent_pages[$this->main_slug])) {
            add_menu_page('RY Plugin', 'RY Plugin', 'read', $this->main_slug, '', $icon);
            foreach ($menu_list as $menu_item) {
                add_submenu_page($this->main_slug, $menu_item['name'], $menu_item['name'], 'manage_options', $menu_item['slug'], $menu_item['function']);
            }
            add_action('all_admin_notices', [$this, 'show_notices']);
        }
    }

    public function show_not_activated(): void
    {
        if (!isset($this->license)) {
            return;
        }

        if ($this->license->is_activated()) {
            return;
        }

        echo '<div class="notice notice-info is-dismissible">';
        echo '<p>' . wp_kses(sprintf(
            /* translators: %1$s: Plugin name, %2$s: License URL */
            __('%1$s: Your <a href="%2$s">license</a> is not activated yet!', 'ry-woocommerce-tools'),
            '<strong>' . esc_html($this->license::$main_class::PLUGIN_NAME) . '</strong>',
            esc_url(admin_url('admin.php?page=ry-license'))
        ), ['strong' => [], 'a' => ['href' => []]]) . '</p>';
        echo '</div>';
    }

    public function show_notices(): void
    {
        $notice = get_transient('ry-notice');
        if (is_array($notice) && count($notice)) {
            foreach ($notice as $status => $message) {
                echo '<div class="notice notice-' . esc_attr($status) . ' is-dismissible">';
                echo '<p>' . implode('</p><p>', array_map('esc_html', $message)) . '</p>';
                echo '</div>';
            }

            set_transient('ry-notice', []);
        }
    }
}
