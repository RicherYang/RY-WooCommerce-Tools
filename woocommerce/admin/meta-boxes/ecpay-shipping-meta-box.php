<?php

class RY_ECPay_Shipping_Meta_Box extends RY_WT_WC_Meta_Box
{
    public static function add_meta_box($post_type, $data_object)
    {
        if ('shop_order' === $post_type || 'woocommerce_page_wc-orders' === $post_type) {
            $order = self::get_order_object($data_object);

            foreach ($order->get_items('shipping') as $item) {
                if (false !== RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($item)) {
                    add_meta_box('ry-ecpay-shipping-info', __('ECPay shipping info', 'ry-woocommerce-tools'), [__CLASS__, 'output'], $post_type, 'normal', 'default');
                    break;
                }
            }
        }
    }

    public static function output($data_object)
    {
        $order = self::get_order_object($data_object);

        $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        } ?>
<div class="ry-shipping-notes">
    <table cellpadding="0" cellspacing="0" class="widefat">
        <thead>
            <tr>
                <th>
                    <?php esc_html_e('ECPay shipping ID', 'ry-woocommerce-tools'); ?>
                </th>
                <th>
                    <?php esc_html_e('Shipping Type', 'ry-woocommerce-tools'); ?>
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
                    <?php esc_html_e('Declare amount', 'ry-woocommerce-tools'); ?>
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
                <?php if ('CVS' == $item['LogisticsType']) { ?>
                <td>
                    <?php echo esc_html(_x('CVS', 'shipping type', 'ry-woocommerce-tools')); ?>
                    <?php if (isset($item['temp'])) {
                        if (1 == $item['temp']) {
                            echo '(' . _x('Normal temperature', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        } elseif (2 == $item['temp']) {
                            echo '(' . _x('Refrigerated', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        } elseif (3 == $item['temp']) {
                            echo '(' . _x('Freezer', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        }
                    } ?>
                </td>
                <td>
                    <?php echo esc_html($item['PaymentNo'] . ' ' . $item['ValidationNo']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['store_ID']); ?>
                </td>
                <?php } else { ?>
                <td>
                    <?php echo esc_html(_x('Home', 'shipping type', 'ry-woocommerce-tools')); ?>
                    <?php if (isset($item['temp'])) {
                        if (1 == $item['temp']) {
                            echo '(' . _x('Normal temperature', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        } elseif (2 == $item['temp']) {
                            echo '(' . _x('Refrigerated', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        } elseif (3 == $item['temp']) {
                            echo '(' . _x('Freezer', 'Transport temp', 'ry-woocommerce-tools') . ')';
                        }
                    } ?>
                </td>
                <td>
                    <?php echo esc_html($item['BookingNote']); ?>
                </td>
                <td></td>
                <?php } ?>
                <td>
                    <?php echo esc_html($item['status_msg']); ?>
                </td>
                <td>
                    <?php echo esc_html($item['amount']); ?>
                </td>
                <td>
                    <?php if('Y' === $item['IsCollection']) {
                        esc_html_e('Yes', 'ry-woocommerce-tools');
                    } elseif('N' === $item['IsCollection']) {
                        esc_html_e('No', 'ry-woocommerce-tools');
                    } else {
                        esc_html_e('Yes', 'ry-woocommerce-tools');
                        echo ' ( ' . esc_html($item['IsCollection']) . ' )';
                    } ?>
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
                    <a class="button" href="<?php echo esc_url(add_query_arg(['orderid' => $order->get_id(), 'id' => $item['ID']], admin_url('admin-post.php?action=ry-print-ecpay-shipping'))); ?>"><?php esc_html_e('Print', 'ry-woocommerce-tools'); ?></a>
                </td>
            </tr>
            <?php
            } ?>
        </tbody>
    </table>
</div>
<?php
    }
}
?>
