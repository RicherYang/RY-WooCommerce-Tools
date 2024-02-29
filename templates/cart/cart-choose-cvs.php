<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-choose-cvs.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.0.4
 */
defined('ABSPATH') || exit;
?>
<tr>
    <th>
        <?php esc_html_e('Convenience store', 'ry-woocommerce-tools') ?>
    </th>
    <td data-title="<?php esc_attr_e('Choose convenience Store', 'ry-woocommerce-tools') ?>">
        <div class="choose_cvs">
            <button type="button" class="button" onclick="RYECPaySendCvsPost('<?php echo esc_attr($post_url); ?>');"><?php esc_html_e('Choose convenience store', 'ry-woocommerce-tools') ?></button>
            <span class="show_choose_cvs_name"><br><?php esc_html_e('Convenience store:', 'ry-woocommerce-tools') ?>
                <span class="choose_cvs_name"></span>
            </span>
        </div>
    </td>
</tr>
