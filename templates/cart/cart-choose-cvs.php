<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-choose-cvs.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.1.7
 */
?>
<tr class="choose_cvs">
	<th><?php echo __('Convenience store', 'ry-woocommerce-tools') ?></th>
	<td data-title="<?php echo esc_attr__('Choose convenience Store', 'ry-woocommerce-tools') ?>">
		<button type="button" class="button" onclick="RYECPaySendCvsPost();"><?php echo __('Choose convenience store', 'ry-woocommerce-tools') ?></button>
		<span class="show_choose_cvs_name"><br><?php echo __('Convenience store:', 'ry-woocommerce-tools') ?><span class="choose_cvs_name"></span>
	</td>
</tr>
