<?php

class RY_ECPay_Shipping_Response extends RY_Abstract_Api_ECPay
{
    public static function init()
    {
        add_action('woocommerce_api_request', [__CLASS__, 'set_do_die']);
        add_action('woocommerce_api_ry_ecpay_map_callback', [__CLASS__, 'map_redirect']);
        add_action('woocommerce_api_ry_ecpay_shipping_callback', [__CLASS__, 'check_shipping_callback']);
        add_action('valid_ecpay_shipping_request', [__CLASS__, 'shipping_callback']);

        if ('yes' === RY_WT::get_option('ecpay_shipping_auto_order_status', 'yes')) {
            add_action('ry_ecpay_shipping_response_status_2063', [__CLASS__, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2073', [__CLASS__, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3018', [__CLASS__, 'shipping_at_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2065', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2070', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2072', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2074', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_2076', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3019', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3020', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3023', [__CLASS__, 'shipping_out_cvs'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3025', [__CLASS__, 'shipping_out_cvs'], 10, 2);

            add_action('ry_ecpay_shipping_response_status_2067', [__CLASS__, 'shipping_completed'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3003', [__CLASS__, 'shipping_completed'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3022', [__CLASS__, 'shipping_completed'], 10, 2);
        }
    }

    public static function map_redirect()
    {
        $cvs_info = [];
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            foreach (['LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'CVSOutSide'] as $key) {
                if (isset($ipn_info[$key])) {
                    $cvs_info[$key] = $ipn_info[$key];
                }
            }
        }

        if (count($cvs_info) == 6) {
            if (isset($ipn_info['ExtraData'])) {
                if (substr($ipn_info['ExtraData'], 0, 2) == 'ry') {
                    $order_ID = (int) substr($ipn_info['ExtraData'], 2);
                    $order = wc_get_order($order_ID);
                    if ($order) {
                        RY_ECPay_Shipping::save_cvs_info($order, $cvs_info);
                        $order->save();
                        wp_safe_redirect(admin_url('post.php?post=' . $order->get_id() . '&action=edit'));
                        die();
                    }
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

            $html = '<!doctype html><html ' . get_language_attributes('html') . '><head><meta charset="' . get_bloginfo('charset', 'display') . '"><title>AutoSubmitForm</title></head><body>';
            $html .= '<form method="post" id="ry-ecpay-map-redirect" action="' . esc_url(wc_get_page_permalink('checkout')) . '" style="display:none;">';
            foreach ($cvs_info as $key => $value) {
                $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
            $html .= '</form>';
            $html .= '<script type="text/javascript">document.getElementById("ry-ecpay-map-redirect").submit();</script>';
            $html .= '</body></html>';

            echo $html;
            die();
        }

        wp_redirect(wc_get_page_permalink('checkout'));
        die();
    }

    public static function check_shipping_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if (self::ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_shipping_request', $ipn_info);
            } else {
                self::die_error();
            }
        }
    }

    protected static function ipn_request_is_valid($ipn_info)
    {
        RY_ECPay_Shipping::log('IPN request: ' . var_export($ipn_info, true));

        list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();
        $check_value = self::get_check_value($ipn_info);
        $ipn_info_check_value = self::generate_check_value($ipn_info, $HashKey, $HashIV, 'md5');
        if ($check_value == $ipn_info_check_value) {
            return true;
        } else {
            RY_ECPay_Shipping::log('IPN request check failed. Response:' . $check_value . ' Self:' . $ipn_info_check_value, 'error');
            return false;
        }
    }

    public static function shipping_callback($ipn_info)
    {
        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('ecpay_shipping_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }
            if (!isset($shipping_list[$ipn_info['AllPayLogisticsID']])) {
                $shipping_list[$ipn_info['AllPayLogisticsID']] = [];
            }
            $old_info = $shipping_list[$ipn_info['AllPayLogisticsID']];
            $shipping_list[$ipn_info['AllPayLogisticsID']]['status'] = self::get_status($ipn_info);
            $shipping_list[$ipn_info['AllPayLogisticsID']]['status_msg'] = self::get_status_msg($ipn_info);
            $shipping_list[$ipn_info['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();

            if (isset($shipping_list[$ipn_info['AllPayLogisticsID']]['ID'])) {
                $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                $order->save_meta_data();
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

            self::die_success();
        }

        RY_ECPay_Shipping::log('Order not found', 'warning');
        self::die_error();
    }

    public static function shipping_at_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_at_cvs_prev_status', ['processing'], $ipn_info, $order))) {
            $order->update_status('ry-at-cvs');
        }
    }

    public static function shipping_out_cvs($ipn_info, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_out_cvs_prev_status', ['ry-at-cvs'], $ipn_info, $order))) {
            $order->update_status('ry-out-cvs');
        }
    }

    public static function shipping_completed($ipn_info, $order)
    {
        $order->update_status('completed');
    }
}
