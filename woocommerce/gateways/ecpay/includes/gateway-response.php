<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_ECPay_Gateway_Response extends RY_WT_ECPay_Api
{
    private static ?self $_instance = null;

    public static function instance(): RY_WT_WC_ECPay_Gateway_Response
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
        add_action('woocommerce_api_ry_ecpay_gateway_return', [$this, 'gateway_return']);
        add_action('woocommerce_api_ry_ecpay_callback', [$this, 'check_callback']);
        add_action('valid_ecpay_gateway_request', [$this, 'doing_callback']);
    }

    public function check_callback(): void
    {
        if (is_array($_POST) && !empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_gateway_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid(array $ipn_info): bool
    {
        $check_value = $this->get_hash_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_ECPay_Gateway::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

            $ipn_info_check_value = $this->generate_hash_value($ipn_info, $api_info['HashKey'], $api_info['HashIV'], 'sha256');
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_ECPay_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }

        return false;
    }

    public function doing_callback($info_value): void
    {
        $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();
        $order_ID = $this->get_order_id($info_value, $api_info['prefix']);
        if ($order = wc_get_order($order_ID)) {
            $transaction_ID = (string) $order->get_transaction_id();
            if ('' === $transaction_ID || !$order->is_paid() || $transaction_ID != $this->get_transaction_id($info_value)) {
                list($payment_type, $payment_subtype) = $this->get_payment_info($info_value);
                $order->set_transaction_id($this->get_transaction_id($info_value));
                $order->update_meta_data('_ecpay_payment_type', $payment_type);
                $order->update_meta_data('_ecpay_payment_subtype', $payment_subtype);
                $order->save();
                $order = wc_get_order($order->get_id());
            }

            $payment_status = $this->get_status($info_value);
            RY_WT_WC_ECPay_Gateway::instance()->log('Found #' . $order->get_id() . ' Payment status: ' . $payment_status, WC_Log_Levels::INFO);

            if (method_exists($this, 'payment_status_' . $payment_status)) {
                call_user_func([$this, 'payment_status_' . $payment_status], $order, $info_value);
            } else {
                $this->payment_status_unknow($order, $info_value);
            }

            do_action('ry_ecpay_gateway_response', $info_value, $order);

            $this->die_success();
        } else {
            RY_WT_WC_ECPay_Gateway::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    protected function get_payment_info($info_value)
    {
        if (isset($info_value['PaymentType'])) {
            $payment_type = $info_value['PaymentType'];
            $payment_type = explode('_', $payment_type);
            if (1 == count($payment_type)) {
                $payment_type[] = '';
            }
            return $payment_type;
        }

        return false;
    }

    protected function payment_status_1($order, $info_value): void
    {
        if ($order->is_paid()) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Payment action with order #' . $order->get_id() . ' status: ' . $order->get_status(), WC_Log_Levels::INFO);
            return;
        }

        $order->add_order_note(__('ECPay payment completed', 'ry-woocommerce-tools'));
        if (isset($info_value['stage'])) {
            $installment = (int) $info_value['stage'];
            if ($installment > 1) {
                $order->add_order_note(sprintf(
                    /* translators: %d number of periods */
                    __('Credit installment to %d', 'ry-woocommerce-tools'),
                    $installment,
                ));
            }
        }
        $order->payment_complete();
    }

    protected function payment_status_2($order, $info_value): void
    {
        if ($order->is_paid()) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Payment action with order #' . $order->get_id() . ' status: ' . $order->get_status(), WC_Log_Levels::INFO);
            return;
        }

        switch ($order->get_payment_method()) {
            case 'ry_ecpay_atm':
                $expireDate = new DateTime($info_value['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_atm_BankCode', $info_value['BankCode']);
                $order->update_meta_data('_ecpay_atm_vAccount', $info_value['vAccount']);
                $order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
            case 'ry_ecpay_bnpl':
                $order->update_meta_data('_ecpay_bnpl_TradeNo', $info_value['BNPLTradeNo']);
                $order->update_meta_data('_ecpay_bnpl_Installment', $info_value['BNPLInstallment']);
                $order->update_status('on-hold');
                break;
        }
    }

    protected function payment_status_10100073($order, $info_value): void
    {
        if ($order->is_paid()) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Payment action with order #' . $order->get_id() . ' status: ' . $order->get_status(), WC_Log_Levels::INFO);
            return;
        }

        switch ($order->get_payment_method()) {
            case 'ry_ecpay_barcode':
                $expireDate = new DateTime($info_value['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_barcode_Barcode1', $info_value['Barcode1']);
                $order->update_meta_data('_ecpay_barcode_Barcode2', $info_value['Barcode2']);
                $order->update_meta_data('_ecpay_barcode_Barcode3', $info_value['Barcode3']);
                $order->update_meta_data('_ecpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
            case 'ry_ecpay_cvs':
                $expireDate = new DateTime($info_value['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_cvs_PaymentNo', $info_value['PaymentNo']);
                $order->update_meta_data('_ecpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
        }
    }

    protected function payment_status_unknow($order, $info_value)
    {
        RY_WT_WC_ECPay_Gateway::instance()->log('Unknow status', WC_Log_Levels::INFO, ['status' => $this->get_status($info_value), 'status_msg' => $this->get_status_msg($info_value)]);
        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
        } else {
            $order->update_status('failed', sprintf(
                /* translators: %1$s: status message, %2$d status code */
                __('Payment unkonw status: %1$s (%2$d)', 'ry-woocommerce-tools'),
                $this->get_status_msg($info_value),
                $this->get_status($info_value),
            ));
        }
    }
}
