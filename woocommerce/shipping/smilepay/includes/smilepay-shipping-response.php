<?php

class RY_SmilePay_Shipping_Response extends RY_Abstract_Api_SmilePay
{
    public static function init()
    {
        add_action('woocommerce_api_ry_smilepay_shipping_map_callback', [__CLASS__, 'check_map_callback']);
        add_action('woocommerce_api_ry_smilepay_shipping_admin_map_callback', [__CLASS__, 'check_admin_map_callback']);
        add_action('woocommerce_api_ry_smilepay_shipping_callback', [__CLASS__, 'shipping_callback']);

        add_action('valid_smilepay_shipping_map_request', [__CLASS__, 'doing_map_callback']);
        add_action('valid_smilepay_shipping_admin_map_request', [__CLASS__, 'doing_admin_map_callback']);
        add_action('valid_smilepay_shipping_request', [__CLASS__, 'doing_callback']);

        if ('yes' == RY_WT::get_option('smilepay_shipping_auto_order_status', 'yes')) {
            add_action('ry_smilepay_shipping_response_status_2', [__CLASS__, 'shipping_at_cvs'], 10, 2);
            add_action('ry_smilepay_shipping_response_status_4', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_smilepay_shipping_response_status_3', [__CLASS__, 'shipping_completed'], 10, 2);
        }
    }

    public static function check_map_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = self::clean_post_data();
            RY_SmilePay_Shipping::log('IPN request: ' . var_export($ipn_info, true));
            if (self::get_status($ipn_info) == 1) {
                do_action('valid_smilepay_shipping_map_request', $ipn_info);
                return;
            }
        }
        self::die_error();
    }

    public static function doing_map_callback($ipn_info)
    {
        $url = wc_get_checkout_url();

        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            RY_SmilePay_Shipping::log('Found order #' . $order->get_id());

            $smse_id = self::get_transaction_id($ipn_info);
            if ($smse_id) {
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }

                $shipping_list[$smse_id] = [
                    'ID' => $smse_id,
                    'amount' => (int) $ipn_info['Amount'],
                    'storeID' => $ipn_info['Storeid'],
                    'PaymentNo' => '',
                    'ValidationNo' => '',
                    'IsCollection' => $ipn_info['Classif'] == 'T' ? 1 : 0,
                    'type' => $ipn_info['Classif_sub'],
                    'status' => 0,
                    'create' => (string) new WC_DateTime(),
                    'edit' => (string) new WC_DateTime()
                ];

                $order->set_shipping_company('');
                $order->set_shipping_address_2('');
                $order->set_shipping_city('');
                $order->set_shipping_state('');
                $order->set_shipping_postcode('');
                $order->set_shipping_address_1($ipn_info['Storeaddress']);
                $order->update_meta_data('_shipping_cvs_store_ID', $ipn_info['Storeid']);
                $order->update_meta_data('_shipping_cvs_store_name', $ipn_info['Storename']);
                $order->update_meta_data('_shipping_cvs_store_address', $ipn_info['Storeaddress']);
                $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
                $order->save();

                if ($ipn_info['Classif'] == 'T') {
                    if (!$order->is_paid()) {
                        $order->update_status($order->has_downloadable_item() ? 'on-hold' : 'processing');
                    }
                    $url = $order->get_checkout_order_received_url();
                } else {
                    $url = RY_SmilePay_Gateway_Api::get_code($order);
                }
            }
        }

        RY_SmilePay_Shipping::log('Redirect: ' . $url);
        wp_redirect($url);
    }

    public static function check_admin_map_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = self::clean_post_data();
            RY_SmilePay_Shipping::log('IPN request: ' . var_export($ipn_info, true));
            if (self::get_status($ipn_info) == 1) {
                do_action('valid_smilepay_shipping_admin_map_request', $ipn_info);
                return;
            }
        }
        wp_redirect(admin_url('edit.php?post_type=shop_order'));
    }

    public static function doing_admin_map_callback($ipn_info)
    {
        $url = admin_url('edit.php?post_type=shop_order');

        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            RY_SmilePay_Shipping::log('Found order #' . $order->get_id());

            $smse_id = self::get_transaction_id($ipn_info);
            if ($smse_id) {
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }

                $shipping_list[$smse_id] = [
                    'ID' => $smse_id,
                    'amount' => (int) $ipn_info['Amount'],
                    'storeID' => $ipn_info['Storeid'],
                    'PaymentNo' => '',
                    'ValidationNo' => '',
                    'IsCollection' => $ipn_info['Classif'] == 'T' ? 1 : 0,
                    'type' => $ipn_info['Classif_sub'],
                    'status' => $ipn_info['Status'],
                    'create' => (string) new WC_DateTime(),
                    'edit' => (string) new WC_DateTime()
                ];

                $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
                $order->save();

                $url = admin_url('post.php?post=' . $order_id . '&action=edit');

                if ('yes' === RY_WT::get_option('smilepay_shipping_auto_get_no', 'yes')) {
                    RY_SmilePay_Shipping_Api::get_code_no($order_id, $smse_id);
                }
            }
        }

        RY_SmilePay_Shipping::log('Redirect: ' . $url);
        wp_redirect($url);
    }

    public static function shipping_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = self::clean_post_data(true);
            RY_SmilePay_Shipping::log('IPN request: ' . var_export($ipn_info, true));
            do_action('valid_smilepay_shipping_request', $ipn_info);
            return;
        }
        self::die_error();
    }

    public static function doing_callback($ipn_info)
    {
        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            RY_SmilePay_Shipping::log('Found order #' . $order->get_id());

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }
            $list_id = self::get_transaction_id($ipn_info);
            if (!isset($shipping_list[$list_id])) {
                $shipping_list[$list_id] = [];
            }
            $old_info = $shipping_list[$list_id];
            $shipping_list[$list_id]['status'] = self::get_status($ipn_info);
            $shipping_list[$list_id]['edit'] = (string) new WC_DateTime();

            if ('yes' === RY_WT::get_option('smilepay_shipping_log_status_change', 'no')) {
                if (isset($old_info['status'])) {
                    if ($old_info['status'] != $shipping_list[$list_id]['status']) {
                        $order->add_order_note(sprintf(
                            /* translators: 1: ECPay ID 2: Old status no 3: New status no */
                            __('%1$s shipping status from %2$s to %3$s', 'ry-woocommerce-tools'),
                            $ipn_info['AllPayLogisticsID'],
                            $old_info['status'],
                            $shipping_list[$list_id]['status']
                        ));
                    }
                }
            }

            $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
            $order->save_meta_data();

            do_action('ry_smilepay_shipping_response_status_' . $shipping_list[$list_id]['status'], $ipn_info, $order);
            do_action('ry_smilepay_shipping_response', $ipn_info, $order);

            self::die_success();
        } else {
            RY_SmilePay_Shipping::log('Order not found #' . $order_id, 'error');
            self::die_error();
        }
    }

    public static function shipping_at_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_smilepay_shipping_at_cvs_prev_status', ['processing'], $ipn_info, $order))) {
            $order->update_status('ry-at-cvs');
        }
    }

    public static function shipping_out_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_smilepay_shipping_out_cvs_prev_status', ['ry-at-cvs'], $ipn_info, $order))) {
            $order->update_status('ry-out-cvs');
        }
    }

    public static function shipping_completed($ipn_info, $order)
    {
        $order->update_status('completed');
    }
}
