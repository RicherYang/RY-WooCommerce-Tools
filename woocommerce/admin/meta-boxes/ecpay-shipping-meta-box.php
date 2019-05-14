<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_Meta_Box {
	public static function add_meta_box($post_type, $post) {
		if( $post_type == 'shop_order' ) {
			global $theorder;
			if( !is_object($theorder) ) {
				$theorder = wc_get_order($post->ID);
			}

			foreach( $theorder->get_items('shipping') as $item_id => $item ) {
				if( RY_ECPay_Shipping::get_order_support_shipping($item) !== false ) {
					add_meta_box('ry-ecpoay-shipping-info', __('ECPay shipping info', 'ry-woocommerce-tools'), [__CLASS__, 'output'], 'shop_order', 'normal', 'high');
					break;
				}
			}
		}
	}

	public static function output($post) {
		global $theorder;

		if( !is_object($theorder) ) {
			$theorder = wc_get_order($post->ID);
		}

		$shipping_list = $theorder->get_meta('_ecpay_shipping_info', true);
		if( !is_array($shipping_list) ) {
			$shipping_list = [];
		}
		?>
		<table cellpadding="0" cellspacing="0" class="widefat">
			<thead>
				<tr>
					<th><?=__('ECPay shipping ID', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Shipping payment no', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Store ID', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Shipping status', 'ry-woocommerce-tools') ?></th>
					<th><?=__('declare amount', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Collection of money', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Shipping status last change time', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Shipping create time', 'ry-woocommerce-tools') ?></th>
					<th><?=__('Shipping booking note', 'ry-woocommerce-tools') ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $shipping_list as $item ) { 
					$item['edit'] = wc_string_to_datetime($item['edit']);
					$item['create'] = wc_string_to_datetime($item['create']);
					?>
					<tr>
						<td><?=$item['ID']; ?></td>
						<?php if( $item['LogisticsType'] == 'CVS' ) { ?>
							<td><?=$item['PaymentNo'] . ' ' . $item['ValidationNo'] ?></td>
							<td><?=$item['store_ID'] ?></td>
						<?php } else { ?>
							<td></td>
							<td></td>
						<?php }?>
						<td><?=$item['status_msg'] ?></td>
						<td><?=$item['amount']; ?></td>
						<td><?=($item['IsCollection'] == 'Y') ? __('Yes') : __('No') ?></td> 
						<?php /* translators: %1$s: date %2$s: time */ ?>
						<td><?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $item['edit']->date_i18n(wc_date_format()), $item['edit']->date_i18n(wc_time_format())) ?></td>
						<td><?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $item['create']->date_i18n(wc_date_format()), $item['create']->date_i18n(wc_time_format())) ?></td>
						<td><button type="button" class="button print_info" data-orderid="<?=$post->ID ?>" data-id="<?=$item['ID'] ?>"><?=__('Print', 'ry-woocommerce-tools') ?></button></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
		wc_enqueue_js(
'jQuery(function($) {
$(".print_info").click(function(){
	window.open(ajaxurl + "?" + $.param({
		action: "RY_ECPay_Shipping_print",
		orderid: $(this).data("orderid"),
		id: $(this).data("id")
	}), "_blank", "toolbar=yes,scrollbars=yes,resizable=yes");
});
});'
		);
	}
}
