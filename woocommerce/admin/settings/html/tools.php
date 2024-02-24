<h2>
    <?php esc_html_e('RY WooCommerce Tools', 'ry-woocommerce-tools'); ?> <?php esc_html_e('Tools', 'ry-woocommerce-tools'); ?>
</h2>

<?php if (isset($time_diff)) { ?>
<p>
    <?php echo esc_html(sprintf(
        /* translators: %1$s server time, %2$d: differ time (second) */
        _n('Server time (%1$s) and Google Public NTP differ is %2$d second', 'Server time (%1$s) and Google Public NTP differ is %2$d seconds', $time_diff, 'ry-woocommerce-tools'),
        current_time('mysql'),
        $time_diff
    )); ?>
</p>
<?php } ?>

<button name="ryt_check_time" class="button-primary" type="submit" value="ryt_check_time"><?php esc_html_e('Check server time', 'ry-woocommerce-tools'); ?></button>
