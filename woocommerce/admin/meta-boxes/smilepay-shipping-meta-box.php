<?php

class RY_SmilePay_Shipping_Meta_Box extends RY_WT_WC_Meta_Box
{
    public static function add_meta_box($post_type, $data_object)
    {
        if ('shop_order' === $post_type || 'woocommerce_page_wc-orders' === $post_type) {
            $order = self::get_order_object($data_object);

            foreach ($order->get_items('shipping') as $item) {
                if (false !== RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($item)) {
                    add_meta_box('ry-smilepay-shipping-info', __('SmilePay shipping info', 'ry-woocommerce-tools'), [__CLASS__, 'output'], $post_type, 'normal', 'default');
                    break;
                }
            }
        }
    }

    public static function output($data_object)
    {
        $order = self::get_order_object($data_object);

        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        } ?>
<div class="ry-shipping-notes">
    <table cellpadding="0" cellspacing="0" class="widefat">
        <thead>
            <tr>
                <th>
                    <?php esc_html_e('SmilePay shipping ID', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Shipping no', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Store ID', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Shipping status', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('declare amount', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Collection of money', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Status change time', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Create time', 'ry-woocommerce-tools'); ?>
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
                    <?php echo esc_html($item['ID']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['PaymentNo'] . ' ' . $item['ValidationNo']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['storeID']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['status']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['amount']); ?>
                </td>
                <td>
                    <?php echo esc_html(($item['IsCollection'] == '1') ? __('Yes') : __('No')); ?>
                </td>
                <td>
                    <?php echo esc_html(sprintf(
                        /* translators: %1$s: date %2$s: time */
                        _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
                        $item['edit']->date_i18n(wc_date_format()),
                        $item['edit']->date_i18n(wc_time_format())
                    )); ?>
                </td>
                <td>
                    <?php echo esc_html(sprintf(
                        /* translators: %1$s: date %2$s: time */
                        _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
                        $item['create']->date_i18n(wc_date_format()),
                        $item['create']->date_i18n(wc_time_format())
                    )); ?>
                </td>
                <td>
                    <?php if (empty($item['PaymentNo'])) {?>
                    <button type="button" class="button get_no" data-orderid="<?php esc_attr($order->get_id()); ?>" data-id="<?php esc_attr($item['ID']); ?>"><?php esc_html_e('Get no', 'ry-woocommerce-tools') ?></button>
                    <?php } else { ?>
                    <button type="button" class="button print_info" data-orderid="<?php esc_attr($order->get_id()); ?>" data-id="<?php esc_attr($item['ID']); ?>"><?php esc_html_e('Print', 'ry-woocommerce-tools') ?></button>
                    <?php } ?>
                </td>
            </tr>
            <?php
            } ?>
        </tbody>
    </table>
</div>
<?php
            wc_enqueue_js(
                'jQuery(function($) {
$(".get_no").click(function(){
    window.location = ajaxurl + "?" + $.param({
        action: "RY_SmilePay_Shipping_get_no",
        orderid: $(this).data("orderid"),
        id: $(this).data("id")
    });
});
$(".print_info").click(function(){
    window.open(ajaxurl + "?" + $.param({
        action: "RY_SmilePay_Shipping_print",
        orderid: $(this).data("orderid"),
        id: $(this).data("id")
    }), "_blank", "toolbar=yes,scrollbars=yes,resizable=yes");
});
});'
            );
    }
}
?>
