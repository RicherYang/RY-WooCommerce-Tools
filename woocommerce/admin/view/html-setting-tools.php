<h2>
    <?=__('RY WooCommerce Tools', 'ry-woocommerce-tools') ?> <?=__('Tools', 'ry-woocommerce-tools') ?>
</h2>

<?php if (isset($time_diff)) { ?>
<p>
    <?=sprintf(
        /* translators: %d: differ time (second) */
        _n('Server time and Google Public NTP differ is %d second', 'Server time and Google Public NTP differ is %d seconds', $time_diff, 'ry-woocommerce-tools'),
    $time_diff
); ?>
</p>
<?php } ?>

<button name="ryt_check_time" class="button-primary" type="submit" value="ryt_check_time"><?php esc_html_e('Check server time', 'ry-woocommerce-tools'); ?></button>
