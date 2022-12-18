<?php

class RY_ECPay_Gateway_Response extends RY_Abstract_Api_ECPay
{
    public static function init()
    {
        add_action('woocommerce_api_request', [__CLASS__, 'set_do_die']);
        add_action('woocommerce_api_ry_ecpay_gateway_return', [__CLASS__, 'gateway_return']);
        add_action('woocommerce_api_ry_ecpay_callback', [__CLASS__, 'check_callback']);
        add_action('valid_ecpay_gateway_request', [__CLASS__, 'doing_callback']);

        add_action('ry_ecpay_gateway_response_status_1', [__CLASS__, 'payment_complete'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_2', [__CLASS__, 'payment_wait_atm'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_10100073', [__CLASS__, 'payment_wait_cvs'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_10100058', [__CLASS__, 'payment_failed'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_10100248', [__CLASS__, 'payment_failed'], 10, 2);
        add_action('ry_ecpay_gateway_response', [__CLASS__, 'add_noaction_note'], 10, 2);
    }

    public static function check_callback(): void
    {
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if (self::ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_gateway_request', $ipn_info);
            } else {
                self::die_error();
            }
        }
    }

    protected static function ipn_request_is_valid($ipn_info): bool
    {
        $check_value = self::get_check_value($ipn_info);
        if ($check_value) {
            RY_ECPay_Gateway::log('IPN request: ' . var_export($ipn_info, true));
            list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

            $ipn_info_check_value = self::generate_check_value($ipn_info, $HashKey, $HashIV, 'sha256');
            if ($check_value == $ipn_info_check_value) {
                return true;
            }
            RY_ECPay_Gateway::log('IPN request check failed. Response:' . $check_value . ' Self:' . $ipn_info_check_value, 'error');
        }

        return false;
    }

    public static function doing_callback($ipn_info): void
    {
        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('ecpay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            $payment_status = self::get_status($ipn_info);
            RY_ECPay_Gateway::log('Found order #' . $order->get_id() . ' Payment status: ' . $payment_status);

            $order = self::set_transaction_info($order, $ipn_info);

            do_action('ry_ecpay_gateway_response_status_' . $payment_status, $ipn_info, $order);
            do_action('ry_ecpay_gateway_response', $ipn_info, $order);

            self::die_success();
        } else {
            RY_ECPay_Gateway::log('Order not found', 'warning');
            self::die_error();
        }
    }

    protected static function set_transaction_info($order, $ipn_info)
    {
        $transaction_id = (string) $order->get_transaction_id();
        if ($transaction_id == '' || !$order->is_paid() || $transaction_id != self::get_transaction_id($ipn_info)) {
            list($payment_type, $payment_subtype) = self::get_payment_info($ipn_info);
            $order->set_transaction_id(self::get_transaction_id($ipn_info));
            $order->update_meta_data('_ecpay_payment_type', $payment_type);
            $order->update_meta_data('_ecpay_payment_subtype', $payment_subtype);
            $order->save();
            $order = wc_get_order($order->get_id());
        }

        return $order;
    }

    protected static function get_payment_info($ipn_info)
    {
        if (isset($ipn_info['PaymentType'])) {
            $payment_type = $ipn_info['PaymentType'];
            $payment_type = explode('_', $payment_type);
            if (count($payment_type) == 1) {
                $payment_type[] = '';
            }
            return $payment_type;
        }

        return false;
    }

    public static function payment_complete($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [__CLASS__, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        $order = self::set_transaction_info($order, $ipn_info);
        $order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
        $order->payment_complete();
    }

    public static function payment_wait_atm($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [__CLASS__, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));
        $order->update_meta_data('_ecpay_atm_BankCode', $ipn_info['BankCode']);
        $order->update_meta_data('_ecpay_atm_vAccount', $ipn_info['vAccount']);
        $order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
        $order->save_meta_data();
        $order->update_status('on-hold');
    }

    public static function payment_wait_cvs($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [__CLASS__, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        list($payment_type, $payment_subtype) = self::get_payment_info($ipn_info);
        $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));
        if ($payment_type == 'CVS') {
            $order->update_meta_data('_ecpay_cvs_PaymentNo', $ipn_info['PaymentNo']);
            $order->update_meta_data('_ecpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
        } else {
            $order->update_meta_data('_ecpay_barcode_Barcode1', $ipn_info['Barcode1']);
            $order->update_meta_data('_ecpay_barcode_Barcode2', $ipn_info['Barcode2']);
            $order->update_meta_data('_ecpay_barcode_Barcode3', $ipn_info['Barcode3']);
            $order->update_meta_data('_ecpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
        }
        $order->save_meta_data();
        $order->update_status('on-hold');
    }

    public static function payment_failed($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [__CLASS__, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
            return;
        }

        $order->update_status('failed', sprintf(
            /* translators: %1$s: status message, %2$d status code */
            __('Payment failed: %1$s (%2$d)', 'ry-woocommerce-tools'),
            self::get_status_msg($ipn_info),
            self::get_status($ipn_info)
        ));
    }

    public static function add_noaction_note($ipn_info, $order): void
    {
        $order->add_order_note(sprintf(
            /* translators: %1$s: status message, %2$d status code */
            __('Payment unkonw status: %1$s (%2$d)', 'ry-woocommerce-tools'),
            self::get_status_msg($ipn_info),
            self::get_status($ipn_info)
        ));
    }
}
