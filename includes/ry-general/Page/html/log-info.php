<?php defined('ABSPATH') or exit; ?>

<h2><?php echo wp_kses(sprintf(
    /* translators: %s: Path of log file */
    __('Viewing log file %s', 'ry-woocommerce-tools'),
    '<code>' . $nice_file_name . '</code>'
), ['code' => []]);
?></h2>

<div style="border:1px solid #ccc;padding:3px;margin-bottom:1em;height:calc(100vh - 250px);overflow:scroll;background-color:#fff;">
    <?php
$fp = fopen($current_file, 'rb'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
$i = 0;
$bg_list = ['#79ecab', '#19b35c', '#0d592e'];
$color_list = [
    'INFO' => '#3858e9',
    'ERROR' => '#cc1818',
];

$count = [];
while (!feof($fp)) {
    $log_text = fgets($fp);
    if (is_string($log_text)) {
        preg_match('/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}) ([^ ]*) (.*)/', $log_text, $matches);
        if ($matches) {
            list($full, $time_str, $level, $message) = $matches;
            $time = new DateTime($time_str);
            $level = trim($level, '[]');
            $log_text = '[' . $time->format('Y-m-d H:i:s') . '] <span style="color:' . ($color_list[$level] ?? '#000') . ';">[' . $level . ']</span> ';

            $message_info = explode(' **CONTEXT** ', $message, 2);
            if (2 === count($message_info)) {
                $log_text .= htmlspecialchars($message_info[0]);
                try {
                    $context = json_decode($message_info[1], null, 512, JSON_THROW_ON_ERROR);
                    $log_text .= '<details><summary>' . __('Details', 'ry-woocommerce-tools') . '</summary>'
                        . htmlspecialchars(wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                        . '</details>';
                } catch (\Throwable $th) {
                }
            } else {
                $log_text .= htmlspecialchars($message);
            }
        } else {
            $log_text = htmlspecialchars($log_text);
        }

        echo wp_kses(
            '<div style="margin:3px 3px 8px;white-space:pre-wrap;text-wrap:nowrap;padding-left:5px;border-left:3px solid ' . $bg_list[++$i % 3] . ';">' . $log_text . '</div>',
            ['div' => ['style' => []], 'span' => ['style' => []], 'details' => [], 'summary' => []]
        );
    }
}
?>
</div>

<a href="<?php echo esc_url(add_query_arg([
    'action' => 'ry-general-admin-logs',
    'action2' => 'download',
    'group' => $current_group,
    'log' => $current_log,
    '_wpnonce' => wp_create_nonce('ry-general-admin-logs'),
], admin_url('admin-post.php'))); ?>" class="button"><?php esc_html_e('Download', 'ry-woocommerce-tools'); ?></a>
&emsp;
<a href="<?php echo esc_url(add_query_arg([
    'action' => 'ry-general-admin-logs',
    'action2' => 'delete',
    'group' => $current_group,
    'log' => $current_log,
    '_wpnonce' => wp_create_nonce('ry-general-admin-logs'),
], admin_url('admin-post.php'))); ?>" class="button"><?php esc_html_e('Delete Permanently', 'ry-woocommerce-tools'); ?></a>
