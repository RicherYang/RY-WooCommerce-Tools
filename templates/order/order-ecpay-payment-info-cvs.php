<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !$order = wc_get_order($order_id) ) {
	return;
}

if( $order->get_payment_method() != 'ry_ecpay_cvs' ) {
	return;
}

if($order->get_meta('_ecpay_payment_type') != 'CVS' ) {
	return;
}
?>
<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?=__('Payment details', 'ry-woocommerce-tools') ?></h2>
	<table class="woocommerce-table woocommerce-table--payment-details payment_details">
		<tbody>
			<tr>
				<td><?=__('CVS code', 'ry-woocommerce-tools') ?></td>
				<td><?=$order->get_meta('_ecpay_cvs_PaymentNo') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', 'ry-woocommerce-tools') ?></td>
				<?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_cvs_ExpireDate')); ?>
				<?php /* translators: %1$s: date %2$s: time */ ?>
				<td><?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format())) ?></td>
			</tr>
		</tbody>
	</table>
</section>
