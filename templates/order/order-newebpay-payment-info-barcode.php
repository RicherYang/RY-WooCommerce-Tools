<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-newebpay-payment-info-barcode.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.0.6
 */
defined('ABSPATH') || exit;

if ('ry_newebpay_barcode' !== $order->get_payment_method()) {
    return;
}

if ('BARCODE' !== $order->get_meta('_newebpay_payment_type')) {
    return;
}
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
                    <span class="free3of9">*<?php echo esc_html($order->get_meta('_newebpay_barcode_Barcode1')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Barcode 2', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <span class="free3of9">*<?php echo esc_html($order->get_meta('_newebpay_barcode_Barcode2')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Barcode 3', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <span class="free3of9">*<?php echo esc_html($order->get_meta('_newebpay_barcode_Barcode3')); ?>*</span>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Payment deadline', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_newebpay_barcode_ExpireDate')); ?>
                    <?php echo esc_html($expireDate->date_i18n(wc_date_format())); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
