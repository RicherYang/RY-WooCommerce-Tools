<?php defined('ABSPATH') or exit; ?>

<?php
/**
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-ecpay-payment-info-atm.php
 *
 * HOWEVER, on occasion RY Tools for WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 3.8.4
 */

if ($order->get_meta('_ecpay_payment_type') !== 'ATM') {
    return;
}

$order_info = [
    'bankCode' => $order->get_meta('_ecpay_atm_BankCode'),
    'vAccount' => $order->get_meta('_ecpay_atm_vAccount'),
    'expireDate' => wc_string_to_datetime($order->get_meta('_ecpay_atm_ExpireDate')),
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
                    <?php esc_html_e('Bank', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php echo esc_html(rywt_bank_code_to_name($order_info['bankCode'])); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('Bank code', 'ry-woocommerce-tools'); ?>
                </td>
                <td>
                    <?php echo esc_html($order_info['bankCode']); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <?php esc_html_e('ATM Bank account', 'ry-woocommerce-tools'); ?>
                </td>
                <td class="ry-atm-account">
                    <?php echo wp_kses('<span>' . wordwrap($order_info['vAccount'], 4, '</span><span>', true) . '</span>', ['span' => []]); ?>
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
