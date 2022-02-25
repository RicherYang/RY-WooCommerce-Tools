<?php
class RY_ECPay_Shipping_Meta_Box
{
    public static function add_meta_box($post_type, $post)
    {
        if ($post_type == 'shop_order') {
            global $theorder;
            if (!is_object($theorder)) {
                $theorder = wc_get_order($post->ID);
            }

            foreach ($theorder->get_items('shipping') as $item_id => $item) {
                if (RY_ECPay_Shipping::get_order_support_shipping($item) !== false) {
                    add_meta_box('ry-ecpay-shipping-info', __('ECPay shipping info', 'ry-woocommerce-tools'), [__CLASS__, 'output'], 'shop_order', 'normal', 'default');
                    break;
                }
            }
        }
    }

    public static function output($post)
    {
        global $theorder;

        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        $shipping_list = $theorder->get_meta('_ecpay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        } ?>
<table cellpadding="0" cellspacing="0" class="widefat">
    <thead>
        <tr>
            <th>
                <?php esc_html_e('ECPay shipping ID', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Shipping Type', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Shipping no', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Store ID', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Shipping status', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('declare amount', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Collection of money', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Shipping status last change time', 'ry-woocommerce-tools') ?>
            </th>
            <th>
                <?php esc_html_e('Shipping create time', 'ry-woocommerce-tools') ?>
            </th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($shipping_list as $item) {
            $item['edit'] = wc_string_to_datetime($item['edit']);
            $item['create'] = wc_string_to_datetime($item['create']); ?>
        <tr>
            <td>
                <?=$item['ID']; ?>
            </td>
            <?php if ($item['LogisticsType'] == 'CVS') { ?>
            <td>
                <?=_x('CVS', 'shipping type', 'ry-woocommerce-tools') ?>
            </td>
            <td>
                <?=$item['PaymentNo'] . ' ' . $item['ValidationNo'] ?>
            </td>
            <td>
                <?=$item['store_ID'] ?>
            </td>
            <?php } else { ?>
            <td>
                <?=_x('Home', 'shipping type', 'ry-woocommerce-tools') ?>
            </td>
            <td>
                <?=$item['BookingNote'] ?>
            </td>
            <td></td>
            <?php } ?>
            <td>
                <?=$item['status_msg'] ?>
            </td>
            <td>
                <?=$item['amount']; ?>
            </td>
            <td>
                <?=($item['IsCollection'] == 'Y') ? __('Yes') : __('No') ?>
            </td>
            <td>
                <?php /* translators: %1$s: date %2$s: time */ ?>
                <?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $item['edit']->date_i18n(wc_date_format()), $item['edit']->date_i18n(wc_time_format())) ?>
            </td>
            <td>
                <?=sprintf(_x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'), $item['create']->date_i18n(wc_date_format()), $item['create']->date_i18n(wc_time_format())) ?>
            </td>
            <td>
                <a class="button" href="<?=esc_url(add_query_arg(['orderid' => $post->ID, 'id' => $item['ID'], 'noheader' => 1], admin_url('admin.php?page=ry_print_ecpay_shipping'))) ?>"><?php esc_html_e('Print booking note', 'ry-woocommerce-tools') ?></a>
            </td>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<?php
    }
}
