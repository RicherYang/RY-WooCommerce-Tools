<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-ecpay-payment-info-bnpl.php
 *
 * HOWEVER, on occasion RY WooCommerce Tools will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.0.6
 */
defined('ABSPATH') || exit;

if ('ry_ecpay_bnpl' !== $order->get_payment_method()) {
    return;
}

if ('BNPL' !== $order->get_meta('_ecpay_payment_type')) {
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
                    <?php esc_html_e('Installment', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php echo esc_html($order->get_meta('_ecpay_bnpl_Installment')); ?>
                </td>
            </tr>
        </tbody>
    </table>
</section>
