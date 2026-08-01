<?php

namespace RY\General\V20260801\Page;

defined('ABSPATH') or exit;

use RY\General\V20260801\AbstractAdminPage;
use RY\General\V20260801\Logs as LogsUtil;

final class Logs extends AbstractAdminPage
{
    private string $log_path = '';

    private array $log_list = [];

    private int $total_size = 0;

    public static function init_menu(): void
    {
        if (!has_action('admin_post_ry-general-logs')) {
            add_filter('ry-plugin/menu_list', [__CLASS__, 'add_menu'], 99999);
            add_action('admin_post_ry-general-logs', [__CLASS__, 'admin_action']);
        }
    }

    public static function add_menu(array $menu_list): array
    {
        $menu_list[] = [
            'name' => __('Logs', 'ry-woocommerce-tools'),
            'slug' => 'ry-logs',
            'capability' => 'manage_options',
            'function' => [__CLASS__, 'pre_show_page'],
        ];

        return $menu_list;
    }

    protected function do_init(): void
    {
        $this->log_list = [];
        $this->log_path = LogsUtil::get_log_directory();

        foreach (new \FilesystemIterator($this->log_path, \FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->isFile() && $file->isReadable()) {
                $this->total_size += $file->getSize();

                if ($file->getExtension() === 'log') {
                    $filename_info = explode('-', $file->getBasename('.log'));
                    array_pop($filename_info);
                    $group = sanitize_title(implode('-', array_slice($filename_info, 0, -3)));
                    $name = sanitize_title(implode('-', array_slice($filename_info, -3, 3)));
                    if (empty($group)) {
                        $group = sanitize_title(implode('-', $filename_info));
                        $name = 'all';
                    }

                    if (!isset($this->log_list[$group])) {
                        $this->log_list[$group] = [];
                    }
                    if (isset($this->log_list[$group][$name])) {
                        $name .= '-' . count($this->log_list[$group]);
                    }
                    $this->log_list[$group][$name] = $file->getFilename();
                }
            }
        }
    }

    public function output_page(): void
    {
        ksort($this->log_list);
        $current_group = sanitize_title(wp_unslash($_GET['group'] ?? ''));
        if (!isset($this->log_list[$current_group])) {
            $current_group = count($this->log_list) ? array_key_first($this->log_list) : '';
        }

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading">' . esc_html__('Logs', 'ry-woocommerce-tools') . '</h1>';
        if ($current_group === '') {
            echo '<p>' . esc_html__('No logs found.', 'ry-woocommerce-tools') . '</p>';
            echo '</div>';
            return;
        }

        krsort($this->log_list[$current_group]);
        $current_log = sanitize_title(wp_unslash($_GET['log'] ?? ''));
        if (!isset($this->log_list[$current_group][$current_log])) {
            $current_log = array_key_first($this->log_list[$current_group]);
        }

        echo '<div style="display:flex;flex-wrap:wrap;gap:1.25em;">';

        $group_list = array_keys($this->log_list);
        $log_list = array_keys($this->log_list[$current_group]);
        $total_size = size_format($this->total_size, 2);
        echo '<div style="flex:0 0 auto;width:auto;">';
        include __DIR__ . '/html/logs-select.php';
        echo '</div>';

        $current_file = realpath($this->log_path . $this->log_list[$current_group][$current_log]);
        if (!str_starts_with($current_file, $this->log_path)) {
            $current_file = '';
        }
        if ($current_file !== '') {
            $nice_file_name = $this->get_nice_file_name($current_file);
            echo '<div style="flex: 1 0 0%;width:100%;font-size:14px;">';
            include __DIR__ . '/html/log-info.php';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    protected function do_admin_action(string $action, string $real_action): void
    {
        if ('ry-general-logs' !== $action) {
            return;
        }

        if ($real_action !== '' && is_callable([$this, $real_action])) {
            $this->$real_action();
        }

        wp_safe_redirect(admin_url('admin.php?page=ry-logs'));
        exit;
    }

    private function dl_log(): void
    {
        check_ajax_referer('dl-log', '_ajax_nonce');

        $current_group = sanitize_title(wp_unslash($_GET['group'] ?? ''));
        $current_log = sanitize_title(wp_unslash($_GET['log'] ?? ''));

        if (isset($this->log_list[$current_group], $this->log_list[$current_group][$current_log])) {
            $current_file = realpath($this->log_path . $this->log_list[$current_group][$current_log]);
            if (str_starts_with($current_file, $this->log_path)) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $this->get_nice_file_name($current_file) . '"');
                readfile($current_file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
                exit;
            }
        }
    }

    private function delete_log(): void
    {
        check_ajax_referer('delete-log', '_ajax_nonce');

        $current_group = sanitize_title(wp_unslash($_GET['group'] ?? ''));
        $current_log = sanitize_title(wp_unslash($_GET['log'] ?? ''));

        if (isset($this->log_list[$current_group], $this->log_list[$current_group][$current_log])) {
            $current_file = realpath($this->log_path . $this->log_list[$current_group][$current_log]);
            if (str_starts_with($current_file, $this->log_path)) {
                if (wp_delete_file($current_file)) {
                    $this->add_notice('success', __('Delete successful.', 'ry-woocommerce-tools'));
                    $current_log = '';

                    if (count($this->log_list[$current_group]) === 0) {
                        $current_group = '';
                    }
                } else {
                    $this->add_notice('error', __('Delete failed.', 'ry-woocommerce-tools'));
                }

                wp_safe_redirect(add_query_arg([
                    'page' => 'ry-logs',
                    'group' => $current_group,
                    'log' => $current_log,
                ], admin_url('admin.php')));
                exit;
            }
        }
    }

    protected function get_nice_file_name($file_path): string
    {
        $nice_file_name = basename($file_path);
        $nice_file_name = explode('-', $nice_file_name);
        array_pop($nice_file_name);
        return implode('-', $nice_file_name);
    }
}
