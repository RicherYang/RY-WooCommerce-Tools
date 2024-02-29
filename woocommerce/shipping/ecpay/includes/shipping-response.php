<?php

class RY_WT_WC_ECPay_Shipping_Response extends RY_WT_WC_ECPay_Api
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_ECPay_Shipping_Response
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('woocommerce_api_request', [$this, 'set_do_die']);
        add_action('woocommerce_api_ry_ecpay_map_callback', [$this, 'map_redirect']);
        add_action('woocommerce_api_ry_ecpay_shipping_callback', [$this, 'check_shipping_callback']);
        add_action('valid_ecpay_shipping_request', [$this, 'shipping_callback']);

        if ('yes' === RY_WT::get_option('ecpay_shipping_auto_order_status', 'yes')) {
            add_action('ry_ecpay_shipping_response_status_2063', [$this, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2073', [$this, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3018', [$this, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2065', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2070', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2072', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2074', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2076', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3019', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3020', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3023', [$this, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3025', [$this, 'shipping_out_cvs'], 10, 2);

            add_action('ry_ecpay_shipping_response_status_2067', [$this, 'shipping_completed'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3003', [$this, 'shipping_completed'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3022', [$this, 'shipping_completed'], 10, 2);
        }
    }

    public function map_redirect()
    {
        $cvs_info = [];
        if (!empty($_POST)) {
            foreach (['LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'CVSOutSide'] as $key) {
                $cvs_info[$key] = wp_unslash($_POST[$key] ?? '');
            }
        }

        if (empty($cvs_info['CVSStoreID'])) {
            wp_redirect(wc_get_checkout_url());
            exit();
        }

        $extra_data = wp_unslash($_POST['ExtraData'] ?? '');
        if (substr($extra_data, 0, 2) == 'ry') {
            $order_ID = (int) substr($extra_data, 2);
            $order = wc_get_order($order_ID);
            if ($order) {
                RY_WT_WC_ECPay_Shipping::instance()->save_order_cvs_info($order, $cvs_info);
                $order->save();
                wp_safe_redirect($order->get_edit_order_url());
                exit();
            }
        }

        $out_island = (int) WC()->session->get('shipping_cvs_out_island');
        if ($cvs_info['CVSOutSide'] != $out_island) {
            $package_key = 0;
            do {
                $stored_rates = WC()->session->get('shipping_for_package_' . $package_key);
                if (empty($stored_rates)) {
                    break;
                }
                WC()->session->set('shipping_for_package_' . $package_key, '');
                $package_key += 1;
            } while (true);
            WC()->session->set('shipping_cvs_out_island', (int) $cvs_info['CVSOutSide']);
        }

        echo '<!doctype html><html><head><title>AutoSubmitForm</title></head><body>';
        echo '<form method="post" id="ry-ecpay-map-redirect" action="' . esc_url(wc_get_checkout_url()) . '">';
        echo '<input type="hidden" name="ry-ecpay-cvsmap-info" value="' . esc_attr(wp_json_encode($cvs_info)) . '">';
        echo '</form>';
        echo '<script type="text/javascript">document.getElementById("ry-ecpay-map-redirect").submit();</script>';
        echo '</body></html>';
        exit();
    }

    public function check_shipping_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_shipping_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid($ipn_info)
    {
        $check_value = $this->get_check_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_ECPay_Shipping::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            list($MerchantID, $HashKey, $HashIV, $cvs_type) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();

            $ipn_info_check_value = $this->generate_check_value($ipn_info, $HashKey, $HashIV, 'md5');
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_ECPay_Shipping::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }
    }

    public function shipping_callback($ipn_info)
    {
        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('ecpay_shipping_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_ECPay_Shipping::instance()->log('Found order #' . $order->get_id(), WC_Log_Levels::INFO);

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }
            if (!isset($shipping_list[$ipn_info['AllPayLogisticsID']])) {
                $shipping_list[$ipn_info['AllPayLogisticsID']] = [];
            }
            $old_info = $shipping_list[$ipn_info['AllPayLogisticsID']];
            $shipping_list[$ipn_info['AllPayLogisticsID']]['status'] = $this->get_status($ipn_info);
            $shipping_list[$ipn_info['AllPayLogisticsID']]['status_msg'] = $this->get_status_msg($ipn_info);
            $shipping_list[$ipn_info['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();

            if (isset($shipping_list[$ipn_info['AllPayLogisticsID']]['ID'])) {
                $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                $order->save();
            }

            if ('yes' === RY_WT::get_option('ecpay_shipping_log_status_change', 'no')) {
                if (isset($old_info['status'])) {
                    if ($old_info['status'] != $shipping_list[$ipn_info['AllPayLogisticsID']]['status']) {
                        $order->add_order_note(sprintf(
                            /* translators: 1: ECPay ID 2: Old status mag 3: Old status no 4: New status mag 5: New status no */
                            __('%1$s shipping status from %2$s(%3$d) to %4$s(%5$d)', 'ry-woocommerce-tools'),
                            $ipn_info['AllPayLogisticsID'],
                            $old_info['status_msg'],
                            $old_info['status'],
                            $shipping_list[$ipn_info['AllPayLogisticsID']]['status_msg'],
                            $shipping_list[$ipn_info['AllPayLogisticsID']]['status']
                        ));
                    }
                }
            }

            do_action('ry_ecpay_shipping_response_status_' . $shipping_list[$ipn_info['AllPayLogisticsID']]['status'], $ipn_info, $order);
            do_action('ry_ecpay_shipping_response', $ipn_info, $order);

            $this->die_success();
        } else {
            RY_WT_WC_ECPay_Shipping::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    public function shipping_at_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_at_cvs_prev_status', ['processing'], $ipn_info, $order))) {
            $order->update_status('ry-at-cvs');
        }
    }

    public function shipping_out_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_out_cvs_prev_status', ['ry-at-cvs'], $ipn_info, $order))) {
            $order->update_status('ry-out-cvs');
        }
    }

    public function shipping_completed($ipn_info, $order)
    {
        $order->update_status('completed');
    }
}
