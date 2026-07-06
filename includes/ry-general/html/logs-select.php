<?php defined('ABSPATH') or exit; ?>

<h2><?php esc_html_e('Log Categories', 'ry-woocommerce-tools'); ?></h2>

<form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="get">
    <input type="hidden" name="page" value="ry-logs">
    <select name="group" size="<?php echo esc_attr(min(10, count($group_list))); ?>" style="padding:0 6px;background: #fff;-webkit-appearance:none;" onchange="this.form.submit();" ondblclick="this.form.submit();">
        <?php
        foreach ($group_list as $group_name) {
            printf(
                '<option value="%1$s" %2$s>%1$s &nbsp;</option>',
                esc_attr($group_name),
                selected($group_name, $current_group, false),
            );
        } ?>
    </select>
</form>

<h2><?php esc_html_e('Log Files', 'ry-woocommerce-tools'); ?></h2>
<form action="<?php echo esc_url(admin_url('admin.php')); ?>" method="get">
    <input type="hidden" name="page" value="ry-logs">
    <input type="hidden" name="group" value="<?php echo esc_attr($current_group); ?>">
    <select name="log" size="<?php echo esc_attr(min(10, max(2, count($log_list)))); ?>" style="padding:0 6px;background: #fff;-webkit-appearance:none;" onchange="this.form.submit();" ondblclick="this.form.submit();">
        <?php
        foreach ($log_list as $log_name) {
            printf(
                '<option value="%1$s" %2$s>%1$s &nbsp;</option>',
                esc_attr($log_name),
                selected($log_name, $current_log, false),
            );
        } ?>
    </select>
</form>
