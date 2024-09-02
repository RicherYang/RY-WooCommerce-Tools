<?php

class RY_WT_WC_SmilePay_Gateway_Response extends RY_WT_WC_SmilePay_Gateway_Api
{
    protected static $_instance = null;

    public static function instance(): RY_WT_WC_SmilePay_Gateway_Response
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('woocommerce_api_ry_smilepay_callback', [$this, 'check_callback']);

        add_action('valid_smilepay_gateway_request', [$this, 'doing_callback']);
    }

    public function check_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = $this->clean_post_data(true);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_smilepay_gateway_request', $ipn_info);
                return;
            }
        }
        $this->die_error();
    }

    protected function ipn_request_is_valid($ipn_info)
    {
        $check_value = $this->get_check_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

            $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
            if ($order = wc_get_order($order_ID)) {
                $ipn_info_check_value = [];
                $ipn_info_check_value[0] = str_pad(substr($Rot_check, -4), 4, '0', STR_PAD_LEFT);
                $ipn_info_check_value[1] = (int) ceil($order->get_total());
                $ipn_info_check_value[1] = str_pad($ipn_info_check_value[1], 8, '0', STR_PAD_LEFT);
                $ipn_info_check_value[2] = $this->get_transaction_id($ipn_info);
                $ipn_info_check_value[2] = str_pad(substr($ipn_info_check_value[2], -4), 4, '9', STR_PAD_LEFT);
                $ipn_info_check_value[2] = preg_replace('/[^\d]/s', '9', $ipn_info_check_value[2]);

                $ipn_info_check_value = implode('', $ipn_info_check_value);
                $strlen = strlen($ipn_info_check_value);
                $odd = $even = 0;
                for ($i = 0; $i < $strlen; ++$i) {
                    if (0 === $i % 2) {
                        $even = $even + (int) $ipn_info_check_value[$i];
                    }
                    if (1 === $i % 2) {
                        $odd = $odd + (int) $ipn_info_check_value[$i];
                    }
                }
                $ipn_info_check_value = $even * 9 + $odd * 3;

                $check_value = (int) $check_value;
                if ($check_value === $ipn_info_check_value) {
                    return true;
                }
            }
        }
        RY_WT_WC_SmilePay_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);

        return false;
    }

    public function doing_callback($ipn_info)
    {
        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('smilepay_gateway_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Found #' . $order->get_id(), WC_Log_Levels::INFO);

            $payment_type = '';
            switch ($ipn_info['Classif']) {
                case 'A':
                    $payment_type = 1;
                    break;
                case 'B':
                    $payment_type = 21;
                    break;
                case 'C':
                    $payment_type = 3;
                    break;
                case 'E':
                    $payment_type = 4;
                    break;
                case 'F':
                    $payment_type = 6;
                    break;
            }

            if (!$order->is_paid()) {
                if ($ipn_info['Amount'] == ceil($order->get_total())) {
                    $order = $this->set_transaction_info($order, $ipn_info, $payment_type);
                    $order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
                    $order->payment_complete();
                }
            }

            switch ($payment_type) {
                case 1:
                    if (0 == $ipn_info['Response_id']) {
                        if ($order->is_paid()) {
                            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
                            $order->save();
                        } else {
                            $order->update_status('failed', sprintf(
                                /* translators: Error status message */
                                __('Payment failed (%s)', 'ry-woocommerce-tools'),
                                $ipn_info['Errdesc'],
                            ));
                        }
                    }
                    break;
            }

            do_action('ry_smilepay_gateway_response', $ipn_info, $order);

            $payment_gateway = wc_get_payment_gateway_by_order($order);
            if (property_exists($payment_gateway, 'get_code_mode')) {
                if (!$payment_gateway->get_code_mode) {
                    wp_redirect($order->get_checkout_order_received_url());
                    return;
                }
            }
            $this->die_success();
        } else {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }
}
