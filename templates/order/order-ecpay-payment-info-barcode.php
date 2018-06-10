<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !$order = wc_get_order($order_id) ) {
	return;
}

if( $order->get_payment_method() != 'ry_ecpay_barcode' ) {
	return;
}

$payment_type = $order->get_meta('_ecpay_payment_type');
?>
<section class="woocommerce-order-details">
	<h2 class="woocommerce-order-details__title"><?=__('Payment details', RY_WT::$textdomain) ?></h2>
	<table class="woocommerce-table woocommerce-table--payment-details payment_details">
		<tbody>
			<tr>
				<td><?=__('Barcode 1', RY_WT::$textdomain) ?></td>
				<td>
					<span class="free3of9">*<?=$order->get_meta('_ecpay_barcode_Barcode1') ?>*</span>
				</td>
			</tr>
			<tr>
				<td><?=__('Barcode 2', RY_WT::$textdomain) ?></td>
				<td>
					<span class="free3of9">*<?=$order->get_meta('_ecpay_barcode_Barcode2') ?>*</span>
				</td>
			</tr>
			<tr>
				<td><?=__('Barcode 3', RY_WT::$textdomain) ?></td>
				<td>
					<span class="free3of9">*<?=$order->get_meta('_ecpay_barcode_Barcode3') ?>*</span>
				</td>
			</tr>
			<tr>
				<td><?=__('Payment deadline', RY_WT::$textdomain) ?></td>
				<td><?=$order->get_meta('_ecpay_barcode_ExpireDate') ?></td>
			</tr>
		</tbody>
	</table>
</section>