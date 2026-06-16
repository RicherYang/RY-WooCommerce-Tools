<?php

defined('ABSPATH') or exit;

class RY_WT_WC_ECPay_Shipping_Response extends RY_WT_ECPay_Api
{
    protected static ?self $_instance = null;

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

        if ('ry-ecpay-map-redirect' === ($_GET['ry-ecpay-map-redirect'] ?? '')) {
            do_action('woocommerce_api_ry_ecpay_map_callback');
        }

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
            add_action('ry_ecpay_shipping_response_status_3308', [$this, 'shipping_completed'], 10, 2);
            add_action('ry_ecpay_shipping_response_status_3309', [$this, 'shipping_completed'], 10, 2);
        }
    }

    public function map_redirect()
    {
        $cvs_info = [];
        if (!empty($_POST)) {
            foreach (['LogisticsSubType', 'CVSStoreID', 'CVSStoreName', 'CVSAddress', 'CVSTelephone', 'CVSOutSide'] as $key) {
                if (isset($_POST[$key])) {
                    $cvs_info[$key] = sanitize_text_field(wp_unslash($_POST[$key]));
                }
            }
        }

        if (6 !== count($cvs_info) || '' === $cvs_info['CVSStoreID']) {
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $extra_data = sanitize_text_field(wp_unslash($_POST['ExtraData'] ?? ''));
        if (str_starts_with($extra_data, 'ry')) {
            if (!did_action('woocommerce_after_register_post_type')) {
                return;
            }

            $order_ID = (int) substr($extra_data, 2);
            $order = wc_get_order($order_ID);
            if ($order) {
                RY_WT_WC_ECPay_Shipping::instance()->save_order_cvs_info($order, $cvs_info);
                $order->save();
                wp_safe_redirect($order->get_edit_order_url());
            } else {
                wp_safe_redirect(admin_url());
            }
            exit;
        }

        add_filter('woocommerce_set_cookie_enabled', '__return_false');
        remove_all_actions('shutdown');

        $args = [
            'method' => 'post',
            'redirect_url' => wc_get_checkout_url(),
            'redirect_data' => [
                'ry-ecpay-cvsmap-info' => rtrim(base64_encode(wp_json_encode($cvs_info)), '='),
            ],
        ];
        wc_get_template('auto-redirect.php', $args, '', RY_WT_PLUGIN_DIR . 'templates/');
        exit;
    }

    public function check_shipping_callback()
    {
        if (is_array($_POST) && !empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_shipping_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid(array $ipn_info): bool
    {
        $check_value = $this->get_hash_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_ECPay_Shipping::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            $api_info = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();

            $ipn_info_check_value = $this->generate_hash_value($ipn_info, $api_info['HashKey'], $api_info['HashIV'], 'md5');
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_ECPay_Shipping::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }

        return false;
    }

    public function shipping_callback($info_value)
    {
        $order_ID = $this->get_order_id($info_value, RY_WT::get_option('ecpay_shipping_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_ECPay_Shipping::instance()->log('Found order #' . $order->get_id(), WC_Log_Levels::INFO);

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }
            if (!isset($shipping_list[$info_value['AllPayLogisticsID']])) {
                $shipping_list[$info_value['AllPayLogisticsID']] = [];
            }
            $old_info = $shipping_list[$info_value['AllPayLogisticsID']];
            $shipping_list[$info_value['AllPayLogisticsID']]['status'] = $this->get_status($info_value);
            $shipping_list[$info_value['AllPayLogisticsID']]['status_msg'] = $this->get_status_msg($info_value);
            $shipping_list[$info_value['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();

            if (isset($shipping_list[$info_value['AllPayLogisticsID']]['ID'])) {
                $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                $order->save();
            }

            if ('yes' === RY_WT::get_option('ecpay_shipping_log_status_change', 'no')) {
                if (isset($old_info['status'])) {
                    if ($old_info['status'] != $shipping_list[$info_value['AllPayLogisticsID']]['status']) {
                        $order->add_order_note(sprintf(
                            /* translators: 1: Shipping ID 2: Old status mag 3: Old status no 4: New status mag 5: New status no */
                            __('%1$s shipping status from %2$s(%3$d) to %4$s(%5$d)', 'ry-woocommerce-tools'),
                            $info_value['AllPayLogisticsID'],
                            $old_info['status_msg'],
                            $old_info['status'],
                            $shipping_list[$info_value['AllPayLogisticsID']]['status_msg'],
                            $shipping_list[$info_value['AllPayLogisticsID']]['status'],
                        ));
                    }
                }
            }

            do_action('ry_ecpay_shipping_response_status_' . $shipping_list[$info_value['AllPayLogisticsID']]['status'], $info_value, $order);
            do_action('ry_ecpay_shipping_response', $info_value, $order);

            $this->die_success();
        } else {
            RY_WT_WC_ECPay_Shipping::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    public function shipping_at_cvs($info_value, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_at_cvs_prev_status', ['processing', 'ry-transporting'], $info_value, $order))) {
            $order->update_status('ry-at-cvs');
        }
    }

    public function shipping_out_cvs($info_value, $order)
    {
        if ($order->has_status(apply_filters('ry_ecpay_shipping_out_cvs_prev_status', ['ry-at-cvs'], $info_value, $order))) {
            $order->update_status('ry-out-cvs');
        }
    }

    public function shipping_completed($info_value, $order)
    {
        $order->update_status('completed');
    }
}
