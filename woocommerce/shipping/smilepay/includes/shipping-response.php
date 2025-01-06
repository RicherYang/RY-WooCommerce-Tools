<?php

class RY_WT_WC_SmilePay_Shipping_Response extends RY_WT_SmilePay_Api
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_SmilePay_Shipping_Response
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('woocommerce_api_ry_smilepay_shipping_map_callback', [$this, 'check_map_callback']);
        add_action('woocommerce_api_ry_smilepay_shipping_admin_callback', [$this, 'check_admin_callback']);
        add_action('woocommerce_api_ry_smilepay_shipping_callback', [$this, 'shipping_callback']);

        add_action('valid_smilepay_shipping_map_request', [$this, 'doing_map_callback'], 10, 2);
        add_action('valid_smilepay_shipping_request', [$this, 'doing_callback']);

        if ('yes' === RY_WT::get_option('smilepay_shipping_auto_order_status', 'yes')) {
            add_action('ry_smilepay_shipping_response_status_2', [$this, 'shipping_at_cvs'], 10, 2);
            add_action('ry_smilepay_shipping_response_status_4', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_smilepay_shipping_response_status_3', [$this, 'shipping_completed'], 10, 2);
        }
    }

    public function check_map_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = $this->clean_post_data();
            RY_WT_WC_SmilePay_Shipping::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            if (1 == $this->get_status($ipn_info)) {
                do_action('valid_smilepay_shipping_map_request', $ipn_info, false);
                return;
            }
        }

        $this->die_error();
    }

    public function check_admin_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = $this->clean_post_data();
            RY_WT_WC_SmilePay_Shipping::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            if (1 == $this->get_status($ipn_info)) {
                do_action('valid_smilepay_shipping_map_request', $ipn_info, true);
                return;
            }
        }

        wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
    }

    public function shipping_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = $this->clean_post_data(true);
            RY_WT_WC_SmilePay_Shipping::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            do_action('valid_smilepay_shipping_request', $ipn_info);
            return;
        }
        $this->die_error();
    }

    public function doing_map_callback($ipn_info, $is_admin)
    {
        $url = $is_admin ? admin_url('edit.php?post_type=shop_order') : wc_get_checkout_url();

        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_SmilePay_Shipping::instance()->log('Found order #' . $order->get_id(), WC_Log_Levels::INFO);

            $transaction_ID = $this->get_transaction_id($ipn_info);
            if ($transaction_ID) {
                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (!isset($shipping_list[$transaction_ID])) {
                    $shipping_list[$transaction_ID] = [];
                }
                $shipping_list[$transaction_ID]['ID'] = $transaction_ID;
                $shipping_list[$transaction_ID]['LogisticsType'] = 'CVS';
                $shipping_list[$transaction_ID]['amount'] = (int) $ipn_info['Amount'];
                $shipping_list[$transaction_ID]['store_ID'] = $ipn_info['Storeid'] ?? '';
                $shipping_list[$transaction_ID]['PaymentNo'] = '';
                $shipping_list[$transaction_ID]['ValidationNo'] = '';
                $shipping_list[$transaction_ID]['type'] = $ipn_info['Classif_sub'];
                $shipping_list[$transaction_ID]['status'] = $this->get_status($ipn_info);
                $shipping_list[$transaction_ID]['create'] = (string) new WC_DateTime();
                $shipping_list[$transaction_ID]['edit'] = (string) new WC_DateTime();

                switch ($ipn_info['Classif']) {
                    case 'T':
                    case 'V':
                        $shipping_list[$transaction_ID]['IsCollection'] = 1;
                        break;
                    case 'U':
                    case 'W':
                        $shipping_list[$transaction_ID]['IsCollection'] = 0;
                        break;
                }

                if (!$is_admin) {
                    $order->set_shipping_company('');
                    $order->set_shipping_address_2('');
                    $order->set_shipping_city('');
                    $order->set_shipping_state('');
                    $order->set_shipping_postcode('');
                    $order->set_shipping_address_1($ipn_info['Storeaddress']);

                    $order->update_meta_data('_shipping_cvs_store_ID', $ipn_info['Storeid']);
                    $order->update_meta_data('_shipping_cvs_store_name', $ipn_info['Storename']);
                    $order->update_meta_data('_shipping_cvs_store_address', $ipn_info['Storeaddress']);
                }
                $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
                $order->save();
                $order = wc_get_order($order_ID);

                if ($is_admin) {
                    $url = $order->get_edit_order_url();
                } else {
                    if ('T' === $ipn_info['Classif']) {
                        if (!$order->is_paid()) {
                            $order->update_status($order->has_downloadable_item() ? 'on-hold' : 'processing');
                        }
                        $url = $order->get_checkout_order_received_url();
                    } else {
                        $url = RY_WT_WC_SmilePay_Gateway_Api::instance()->get_code($order);
                    }
                }
            }
        }

        wp_redirect($url);
    }

    public function doing_callback($ipn_info)
    {
        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_SmilePay_Shipping::instance()->log('Found order #' . $order->get_id(), WC_Log_Levels::INFO);

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }
            $transaction_ID = $this->get_transaction_id($ipn_info);
            if (!isset($shipping_list[$transaction_ID])) {
                $shipping_list[$transaction_ID] = [];
            }
            $old_info = $shipping_list[$transaction_ID];
            $shipping_list[$transaction_ID]['status'] = $this->get_status($ipn_info);
            $shipping_list[$transaction_ID]['edit'] = (string) new WC_DateTime();

            if ('yes' === RY_WT::get_option('smilepay_shipping_log_status_change', 'no')) {
                if (isset($old_info['status'])) {
                    if ($old_info['status'] != $shipping_list[$transaction_ID]['status']) {
                        $order->add_order_note(sprintf(
                            /* translators: 1: ECPay ID 2: Old status no 3: New status no */
                            __('%1$s shipping status from %2$s to %3$s', 'ry-woocommerce-tools'),
                            $ipn_info['AllPayLogisticsID'],
                            $old_info['status'],
                            $shipping_list[$transaction_ID]['status'],
                        ));
                    }
                }
            }

            $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
            $order->save();

            do_action('ry_smilepay_shipping_response_status_' . $shipping_list[$transaction_ID]['status'], $ipn_info, $order);
            do_action('ry_smilepay_shipping_response', $ipn_info, $order);

            $this->die_success();
        } else {
            RY_WT_WC_SmilePay_Shipping::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    public function shipping_at_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_smilepay_shipping_at_cvs_prev_status', ['processing', 'ry-transporting'], $ipn_info, $order))) {
            $order->update_status('ry-at-cvs');
        }
    }

    public function shipping_out_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_smilepay_shipping_out_cvs_prev_status', ['ry-at-cvs'], $ipn_info, $order))) {
            $order->update_status('ry-out-cvs');
        }
    }

    public function shipping_completed($ipn_info, $order)
    {
        $order->update_status('completed');
    }
}
