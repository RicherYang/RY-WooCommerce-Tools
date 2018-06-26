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
					add_meta_box('ry-ecpoay-shipping-info', __('CVS info', RY_WT::$textdomain), 'RY_ECPay_Shipping_Meta_Box::output', 'shop_order', 'normal', 'high');
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

		$cvs_info_list = $theorder->get_meta('_shipping_cvs_info', true);
		if( !is_array($cvs_info_list) ) {
			$cvs_info_list = array();
		}
		?>
		<table cellpadding="0" cellspacing="0" class="widefat">
			<thead>
				<tr>
					<th><?=__('ECPay shipping ID', RY_WT::$textdomain) ?></th>
					<th><?=__('Shipping payment no', RY_WT::$textdomain) ?></th>
					<th><?=__('Store ID', RY_WT::$textdomain) ?></th>
					<th><?=__('Shipping status', RY_WT::$textdomain) ?></th>
					<th><?=__('declare amount', RY_WT::$textdomain) ?></th>
					<th><?=__('Collection of money', RY_WT::$textdomain) ?></th>
					<th><?=__('Shipping status last change time', RY_WT::$textdomain) ?></th>
					<th><?=__('Shipping create time', RY_WT::$textdomain) ?></th>
					<th><?=__('Shipping booking note', RY_WT::$textdomain) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach( $cvs_info_list as $item ) { 
					$item['edit'] = wc_string_to_datetime($item['edit']);
					$item['create'] = wc_string_to_datetime($item['create']);
					?>
					<tr>
						<td><?=$item['ID']; ?></td>
						<td><?=$item['PaymentNo'] . ' ' . $item['ValidationNo'] ?></td>
						<td><?=$item['store_ID'] ?></td>
						<td><?=$item['status_msg'] ?></td>
						<td><?=$item['amount']; ?></td>
						<td><?=($item['IsCollection'] == 'Y') ? __('Yes') : __('No') ?></td> 
						<?php /* translators: %1$s: date %2$s: time */ ?>
						<td><?=sprintf(_x('%1$s %2$s', 'Datetime', RY_WT::$textdomain), $item['edit']->date_i18n(wc_date_format()), $item['edit']->date_i18n(wc_time_format())) ?></td>
						<td><?=sprintf(_x('%1$s %2$s', 'Datetime', RY_WT::$textdomain), $item['create']->date_i18n(wc_date_format()), $item['create']->date_i18n(wc_time_format())) ?></td>
						<td><button type="button" class="button print_info" data-orderid="<?=$post->ID ?>" data-id="<?=$item['ID'] ?>"><?=__('Print', RY_WT::$textdomain) ?></button></td>
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
