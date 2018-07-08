<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !$order = wc_get_order($order_id) ) {
	return;
}

if( $order->get_payment_method() != 'ry_ecpay_atm' ) {
	return;
}

$payment_type = $order->get_meta('_ecpay_payment_type');
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
				<?php /* translators: %1$s: date %2$s: time */ ?>
				<td><?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format())) ?></td>
			</tr>
		</tbody>
	</table>
</section>
