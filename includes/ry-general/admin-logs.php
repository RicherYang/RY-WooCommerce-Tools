<?php

defined('ABSPATH') or exit;

if (!class_exists('RY_Admin_Logs', false)) {
    include_once __DIR__ . '/abstract-admin-page.php';

    final class RY_Admin_Logs extends RY_Abstract_Admin_Page
    {
        protected static $_instance = null;

        protected string $log_path = '';

        protected array $log_list = [];

        public static function init_menu(): void
        {
            add_filter('ry-plugin/menu_list', [__CLASS__, 'add_menu'], 9999);
            add_action('admin_post_ry-general-admin-logs', [__CLASS__, 'admin_action']);
        }

        public static function add_menu(array $menu_list): array
        {
            $menu_list[] = [
                'name' => __('Logs', 'ry-woocommerce-tools'),
                'slug' => 'ry-logs',
                'function' => [__CLASS__, 'pre_show_page'],
            ];

            return $menu_list;
        }

        protected function do_init(): void
        {
            $this->log_path = WP_CONTENT_DIR . '/ry-logs';
            $this->log_list = [];

            if (!is_dir($this->log_path)) {
                wp_mkdir_p($this->log_path);
            }

            if (!is_file($this->log_path . '/.htaccess')) {
                @file_put_contents($this->log_path . '/.htaccess', 'deny from all');
            }
            if (!is_file($this->log_path . '/index.html')) {
                @file_put_contents($this->log_path . '/index.html', '');
            }

            $this->log_path = realpath($this->log_path) . DIRECTORY_SEPARATOR;

            $files = @scandir($this->log_path, SCANDIR_SORT_ASCENDING);
            if (!empty($files)) {
                foreach ($files as $value) {
                    if (!in_array($value, ['.', '..'], true)) {
                        if (!is_dir($this->log_path . $value) && strstr($value, '.log')) {
                            $file = str_replace('.log', '', $value);
                            $file = explode('-', $file);
                            array_pop($file);
                            $group = sanitize_title(implode('-', array_slice($file, 0, -3)));
                            $name = sanitize_title(implode('-', array_slice($file, -3, 3)));
                            if (empty($group)) {
                                $group = sanitize_title(implode('-', $file));
                                $name = 'all';
                            }

                            if (!isset($this->log_list[$group])) {
                                $this->log_list[$group] = [];
                            }
                            if (isset($this->log_list[$group][$name])) {
                                $name .= '-' . count($this->log_list[$group]);
                            }
                            $this->log_list[$group][$name] = $value;
                        }
                    }
                }
            }
        }

        public function output_page(): void
        {
            $current_group = sanitize_title(wp_unslash($_GET['group'] ?? ''));
            if (!isset($this->log_list[$current_group])) {
                $current_group = array_key_first($this->log_list);
            }

            $current_log = sanitize_title(wp_unslash($_GET['log'] ?? ''));
            if (!isset($this->log_list[$current_group][$current_log])) {
                $current_log = array_key_first($this->log_list[$current_group]);
            }

            $current_file = realpath($this->log_path . $this->log_list[$current_group][$current_log]);
            if (!str_starts_with($current_file, $this->log_path)) {
                $current_file = '';
            }

            echo '<div class="wrap"><h1>' . esc_html__('Logs', 'ry-woocommerce-tools') . '</h1>';
            echo '<div style="display:flex;flex-wrap:wrap;gap:1.25em;">';

            $group_list = array_keys($this->log_list);
            $log_list = array_keys($this->log_list[$current_group]);
            echo '<div style="flex:0 0 auto;width:auto;">';
            include __DIR__ . '/html/logs-select.php';
            echo '</div>';

            if ($current_file !== '') {
                $nice_file_name = $this->get_nice_file_name($current_file);
                echo '<div style="flex: 1 0 0%;width:100%;font-size:14px;">';
                include __DIR__ . '/html/log-info.php';
                echo '</div>';
            }

            echo '</div>';
            echo '</div>';
        }

        public function do_admin_action(string $action): void
        {
            if ('ry-general-admin-logs' !== $action) {
                return;
            }

            if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'ry-general-admin-logs')) {
                wp_die('Invalid nonce');
            }

            $current_group = sanitize_title(wp_unslash($_GET['group'] ?? ''));
            $current_log = sanitize_title(wp_unslash($_GET['log'] ?? ''));

            if (isset($this->log_list[$current_group], $this->log_list[$current_group][$current_log])) {
                $current_file = realpath($this->log_path . $this->log_list[$current_group][$current_log]);
                if (str_starts_with($current_file, $this->log_path)) {
                    if (sanitize_key($_GET['action2'] ?? '') === 'download') {
                        header('Content-Type: text/plain');
                        header('Content-Disposition: attachment; filename="' . $this->get_nice_file_name($current_file) . '"');
                        readfile($current_file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
                        exit;
                    }

                    if (sanitize_key($_GET['action2'] ?? '') === 'delete') {
                        if (wp_delete_file($current_file)) {
                            $this->add_notice('success', __('Delete successful.', 'ry-woocommerce-tools'));
                            $current_log = '';

                            if (count($this->log_list[$current_group]) === 0) {
                                $current_group = '';
                            }
                        } else {
                            $this->add_notice('error', __('Delete failed.', 'ry-woocommerce-tools'));
                        }
                    }
                }
            }

            wp_safe_redirect(add_query_arg([
                'page' => 'ry-logs',
                'group' => $current_group,
                'log' => $current_log,
            ], admin_url('admin.php')));
            exit;
        }

        protected function get_nice_file_name($file_path): string
        {
            $nice_file_name = basename($file_path);
            $nice_file_name = explode('-', $nice_file_name);
            array_pop($nice_file_name);
            return implode('-', $nice_file_name) . '.log';
        }
    }

    RY_Admin_Logs::init_menu();
}
