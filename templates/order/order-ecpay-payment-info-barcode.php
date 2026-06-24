<?php defined('ABSPATH') or exit; ?>

<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-ecpay-payment-info-barcode.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.8.4
 */

if ($order->get_meta('_ecpay_payment_type') !== 'BARCODE') {
    return;
}

$order_info = [
    'barcode1' => $order->get_meta('_ecpay_barcode_Barcode1'),
    'barcode2' => $order->get_meta('_ecpay_barcode_Barcode2'),
    'barcode3' => $order->get_meta('_ecpay_barcode_Barcode3'),
    'expireDate' => wc_string_to_datetime($order->get_meta('_ecpay_barcode_ExpireDate')),
];
?>
<section class="woocommerce-order-details">
    <h2 class="woocommerce-order-details__title">
        <?php esc_html_e('Payment details', 'ry-woocommerce-tools'); ?>
    </h2>
    <table class="woocommerce-table woocommerce-table--payment-details payment_details">
        <tbody>
            <tr>
                <td>
                    <?php esc_html_e('Barcode 1', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <span class="free3of9">*<?php echo esc_html($order_info['barcode1']); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Barcode 2', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <span class="free3of9">*<?php echo esc_html($order_info['barcode2']); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Barcode 3', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <span class="free3of9">*<?php echo esc_html($order_info['barcode3']); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Payment deadline', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php echo esc_html(sprintf(
                        /* translators: %1$s: date %2$s: time */
                        _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
                        $order_info['expireDate']->date_i18n(wc_date_format()),
                        $order_info['expireDate']->date_i18n(wc_time_format()),
                    )); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
