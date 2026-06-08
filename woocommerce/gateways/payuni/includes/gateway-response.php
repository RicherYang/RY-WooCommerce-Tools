<?php

defined('ABSPATH') or exit;

class RY_WT_WC_PAYUNi_Gateway_Response extends RY_WT_PAYUNi_Api
{
    protected static ?self $_instance = null;

    public static function instance(): RY_WT_WC_PAYUNi_Gateway_Response
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
        add_action('woocommerce_api_ry_payuni_gateway_return', [$this, 'gateway_return']);
        add_action('woocommerce_api_ry_payuni_callback', [$this, 'check_callback']);
        add_action('valid_payuni_gateway_request', [$this, 'doing_callback']);
    }

    public function check_callback()
    {
        if (is_array($_POST) && !empty($_POST)) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $ipn_info = wp_unslash($_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_payuni_gateway_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid(array $ipn_info): bool
    {
        $check_value = $this->get_hash_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

            $info_value = $this->get_info_value($ipn_info);
            $ipn_info_check_value = $this->generate_hash_value($info_value, $HashKey, $HashIV);
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_PAYUNi_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }

        return false;
    }

    public function doing_callback($ipn_info)
    {
        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $ipn_info = $this->get_info_value($ipn_info);
        $ipn_info = $this->args_decrypt($ipn_info, $HashKey, $HashIV);
        parse_str($ipn_info, $ipn_info);
        RY_WT_WC_PAYUNi_Gateway::instance()->log('IPN request decrypt', WC_Log_Levels::INFO, ['data' => $ipn_info]);

        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('payuni_gateway_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            $payment_status = $this->get_status($ipn_info);
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Found #' . $order->get_id() . ' Payment status: ' . $payment_status, WC_Log_Levels::INFO);

            $transaction_ID = (string) $order->get_transaction_id();
            if ($transaction_ID === '' || $transaction_ID != $this->get_transaction_id($ipn_info)) {
                $payment_type = $this->get_payment_type($ipn_info);
                $order->set_transaction_id($this->get_transaction_id($ipn_info));
                $order->update_meta_data('_payuni_payment_type', $payment_type);
                $order->save();
                $order = wc_get_order($order_ID);
            }

            if (method_exists($this, 'payment_status_' . $payment_status)) {
                call_user_func([$this, 'payment_status_' . $payment_status], $order, $ipn_info);
            } else {
                $this->payment_status_unknow($order, $ipn_info);
            }

            do_action('ry_payuni_gateway_response_status_' . $payment_status, $ipn_info, $order);
            do_action('ry_payuni_gateway_response', $ipn_info, $order);

            $this->die_success();
        } else {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    protected function payment_status_SUCCESS($order, $ipn_info)
    {
        $order = wc_get_order($order);
        if (!$order->is_paid()) {
            if ($ipn_info['TradeStatus'] == 1) {
                $order->add_order_note(__('PAYUNi payment completed', 'ry-woocommerce-tools'));
                $order->payment_complete();
            } elseif ($ipn_info['TradeStatus'] == 0) {
                switch ($this->get_payment_type($ipn_info)) {
                    case '2':
                        $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

                        $order->update_meta_data('_payuni_atm_BankType', $ipn_info['BankType']);
                        $order->update_meta_data('_payuni_atm_PayNo', $ipn_info['PayNo']);
                        $order->update_meta_data('_payuni_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                        $order->save();

                        $order->update_status('on-hold');
                        break;
                    case '3':
                        $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

                        $order->update_meta_data('_payuni_cvs_Store', $ipn_info['Store']);
                        $order->update_meta_data('_payuni_cvs_PayNo', $ipn_info['PayNo']);
                        $order->update_meta_data('_payuni_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                        $order->save();

                        $order->update_status('on-hold');
                        break;
                }
            }
        }
    }

    protected function payment_status_unknow($order, $ipn_info)
    {
        RY_WT_WC_PAYUNi_Gateway::instance()->log('Unknow status', WC_Log_Levels::INFO, ['status' => $this->get_status($ipn_info), 'status_msg' => $this->get_status_msg($ipn_info)]);
        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
        } else {
            $order->update_status('failed', sprintf(
                /* translators: 1: Error status code 2: Error status message */
                __('Payment failed: %1$s (%2$s)', 'ry-woocommerce-tools'),
                $this->get_status($ipn_info),
                $this->get_status_msg($ipn_info),
            ));
        }
    }
}
