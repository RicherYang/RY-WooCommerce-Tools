<?php defined('ABSPATH') or exit; ?>

<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/callback-check-failed-limited.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 2026.11.19
 */

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php
/* translators: %s: provider. */
$text = __('Callback check failed for provider %s.', 'ry-woocommerce-tools');
?>
<p><?php printf(esc_html($text), esc_html($provider)); ?></p>
<?php
/* translators: %d: seconds. */
$text = __('All callback will blocked in %d seconds.', 'ry-woocommerce-tools');
?>
<p><?php printf(esc_html($text), esc_html($seconds)); ?></p>

<?php

/**
 * Show user-defined additonal content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?>
