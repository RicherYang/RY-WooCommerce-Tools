<?php defined('ABSPATH') or exit; ?>

<?php
use RY\General\V20260810\Utils;

?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e('Enable tracking', 'ry-woocommerce-tools'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e('Enable tracking', 'ry-woocommerce-tools'); ?></span></legend>
                <label for="tracking"><input name="tracking" type="checkbox" id="tracking" value="yes" <?php checked(Utils::string_to_bool(get_option('RY_General_tracking', 'yes'))); ?>>
                    <?php esc_html_e('Allow usage of RY Plugin to be tracked', 'ry-woocommerce-tools'); ?></label>
                <p class="description">
                    <?php echo wp_kses(sprintf(
                        /* translators: %s: link to usage tracking information */
                        __('Read about what usage data is tracked at: %s .', 'ry-woocommerce-tools'),
                        '<a href="https://ry-plugin.com/usage-tracking" target="_blank">https://ry-plugin.com/usage-tracking</a>'
                    ),
                        ['a' => ['href' => [], 'target' => [], 'rel' => []]]
                    ); ?>
                </p>
            </fieldset>
        </td>
    </tr>
</table>
