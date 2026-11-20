<?php defined('ABSPATH') or exit; ?>

<?php

/**
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/callback-check-failed-limited.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.2.9
 */

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html(wp_strip_all_tags($email_heading));
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: provider. */
$text = __('Callback check failed for provider %s.', 'ry-woocommerce-tools');
printf(esc_html($text), esc_html($provider)) . "\n\n";
/* translators: %d: seconds. */
$text = __('All callback will blocked in %d seconds.', 'ry-woocommerce-tools');
printf(esc_html($text), esc_html($seconds)) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Show user-defined additonal content - this is set in each email's settings.
 */
if ($additional_content) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}

echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
?>
