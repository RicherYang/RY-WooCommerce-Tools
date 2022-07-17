<?php
final class RY_NewebPay_Shipping_admin
{
    public static function init()
    {
        include_once RY_WT_PLUGIN_DIR . 'woocommerce/admin/meta-boxes/newebpay-shipping-meta-box.php';

        add_action('add_meta_boxes', ['RY_NewebPay_Shipping_Meta_Box', 'add_meta_box'], 40, 2);

        if ('yes' === RY_WT::get_option('newebpay_shipping', 'no')) {
            add_filter('woocommerce_order_actions', [__CLASS__, 'add_order_actions']);
            add_action('woocommerce_order_action_send_at_cvs_email', ['RY_NewebPay_Shipping', 'send_at_cvs_email']);
        }
    }

    public static function add_order_actions($order_actions)
    {
        global $theorder, $post;
        if (!is_object($theorder)) {
            $theorder = wc_get_order($post->ID);
        }

        foreach ($theorder->get_items('shipping') as $item) {
            if (RY_NewebPay_Shipping::get_order_support_shipping($item) !== false) {
                if ($theorder->has_status(['ry-at-cvs'])) {
                    $order_actions['send_at_cvs_email'] = __('Resend at cvs notification', 'ry-woocommerce-tools');
                }
            }
        }
        return $order_actions;
    }
}

RY_NewebPay_Shipping_admin::init();
