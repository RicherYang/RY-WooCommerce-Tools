<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-ecpay-payment-info-cvs.php
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

if ('ry_ecpay_cvs' !== $order->get_payment_method()) {
    return;
}

if ('CVS' !== $order->get_meta('_ecpay_payment_type')) {
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
                    <?php esc_html_e('CVS code', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php echo esc_html($order->get_meta('_ecpay_cvs_PaymentNo')); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Payment deadline', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php $expireDate = wc_string_to_datetime($order->get_meta('_ecpay_cvs_ExpireDate')); ?>
                    <?php echo esc_html(sprintf(
                        /* translators: %1$s: date %2$s: time */
                        _x('%1$s %2$s', 'Datetime', 'ry-woocommerce-tools'),
                        $expireDate->date_i18n(wc_date_format()),
                        $expireDate->date_i18n(wc_time_format())
                    )); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
