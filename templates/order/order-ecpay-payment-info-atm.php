<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-ecpay-payment-info-atm.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0.17
 */

defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !$order = wc_get_order($order_id) ) {
	return;
}

if( $order->get_payment_method() != 'ry_ecpay_atm' ) {
	return;
}

if($order->get_meta('_ecpay_payment_type') != 'ATM' ) {
	return;
}
?>
<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?=__('Payment details', 'ry-woocommerce-tools') ?></h2>
	<table class="woocommerce-table woocommerce-table--payment-details payment_details">
		<tbody>
			<tr>
				<td><?=__('Bank', 'ry-woocommerce-tools') ?></td>
				<td><?=__($order->get_meta('_ecpay_payment_subtype'), 'ry-woocommerce-tools') ?></td>
			</tr>
			<tr>
				<td><?=__('Bank code', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_atm_BankCode') ?></td>
			</tr>
			<tr>
				<td><?=__('ATM Bank account', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_atm_vAccount') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', 'ry-woocommerce-tools') ?></td>
				<?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_atm_ExpireDate')); ?>
				<td><?=$expireDate->date_i18n(wc_date_format()); ?></td>
			</tr>
		</tbody>
	</table>
</section>
