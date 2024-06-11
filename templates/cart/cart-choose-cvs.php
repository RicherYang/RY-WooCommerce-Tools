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

$checkout = WC()->checkout();
?>
<tr>
    <th>
        <?php esc_html_e('Convenience store', 'ry-woocommerce-tools') ?>
    </th>
    <td data-title="<?php esc_attr_e('Choose convenience store', 'ry-woocommerce-tools') ?>">
        <button type="button" class="button ry-choose-cvs" data-ry-url="<?php echo esc_attr($post_url); ?>"><?php esc_html_e('Choose convenience store', 'ry-woocommerce-tools') ?></button>
        <div class="ry-cvs-store-info" style="display:none">
            <span>
                <?php esc_html_e('Store name:', 'ry-woocommerce-tools') ?>
                <span class="store-name"></span><br>
            </span>
            <span>
                <?php esc_html_e('Store address:', 'ry-woocommerce-tools') ?>
                <span class="store-address"></span><br>
            </span>
            <span>
                <?php esc_html_e('Store telephone:', 'ry-woocommerce-tools') ?>
                <span class="store-telephone"></span><br>
            </span>
            <?php
$fields = $checkout->get_checkout_fields('rycvs');
foreach ($fields as $key => $field) {
    echo '<input type="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($checkout->get_value($key)) . '"/>';
} ?>
        </div>
    </td>
</tr>
