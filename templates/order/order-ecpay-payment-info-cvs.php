<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !$order = wc_get_order($order_id) ) {
	return;
}

if( $order->get_payment_method() != 'ry_ecpay_cvs' ) {
	return;
}

$payment_type = $order->get_meta('_ecpay_payment_type');
?>
<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?=__('Payment details', RY_WT::$textdomain) ?></h2>
	<table class="woocommerce-table woocommerce-table--payment-details payment_details">
		<tbody>
			<tr>
				<td><?=__('CVS code', RY_WT::$textdomain) ?></td>
				<td><?=$order->get_meta('_ecpay_cvs_PaymentNo') ?></td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', RY_WT::$textdomain) ?></td>
				<?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_cvs_ExpireDate')); ?>
				<?php /* translators: %1$s: date %2$s: time */ ?>
				<td><?=sprintf(_x('%1$s %2$s', 'Datetime', RY_WT::$textdomain), $expireDate->date_i18n(wc_date_format()), $expireDate->date_i18n(wc_time_format())) ?></td>
			</tr>
		</tbody>
	</table>
</section>
