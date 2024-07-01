<?php

class RY_WT_WC_ECPay_Gateway_Response extends RY_WT_ECPay_Api
{
    protected static $_instance = null;

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

        add_action('ry_ecpay_gateway_response_status_1', [$this, 'payment_complete'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_2', [$this, 'payment_wait_atm'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_10100073', [$this, 'payment_wait_cvs'], 10, 2);
        add_action('ry_ecpay_gateway_response_status_10100058', [$this, 'payment_failed'], 10, 2); // Pay Fail
        add_action('ry_ecpay_gateway_response_status_10100248', [$this, 'payment_failed'], 10, 2); // 拒絕交易
        add_action('ry_ecpay_gateway_response_status_10100282', [$this, 'payment_failed'], 10, 2); // 3D授權未完成
        add_action('ry_ecpay_gateway_response', [$this, 'add_noaction_note'], 10, 2);
    }

    public function check_callback(): void
    {
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_ecpay_gateway_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid($ipn_info): bool
    {
        $check_value = $this->get_check_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_ECPay_Gateway::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

            $ipn_info_check_value = $this->generate_check_value($ipn_info, $HashKey, $HashIV, 'sha256');
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_ECPay_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }

        return false;
    }

    public function doing_callback($ipn_info): void
    {
        $order_ID = $this->get_order_id($ipn_info, RY_WT::get_option('ecpay_gateway_order_prefix'));
        if ($order = wc_get_order($order_ID)) {
            $payment_status = $this->get_status($ipn_info);
            RY_WT_WC_ECPay_Gateway::instance()->log('Found #' . $order->get_id() . ' Payment status: ' . $payment_status, WC_Log_Levels::INFO);

            $order = $this->set_transaction_info($order, $ipn_info);

            do_action('ry_ecpay_gateway_response_status_' . $payment_status, $ipn_info, $order);
            do_action('ry_ecpay_gateway_response', $ipn_info, $order);

            $this->die_success();
        } else {
            RY_WT_WC_ECPay_Gateway::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    protected function set_transaction_info($order, $ipn_info)
    {
        $transaction_ID = (string) $order->get_transaction_id();
        if ('' === $transaction_ID || !$order->is_paid() || $transaction_ID != $this->get_transaction_id($ipn_info)) {
            list($payment_type, $payment_subtype) = $this->get_payment_info($ipn_info);
            $order->set_transaction_id($this->get_transaction_id($ipn_info));
            $order->update_meta_data('_ecpay_payment_type', $payment_type);
            $order->update_meta_data('_ecpay_payment_subtype', $payment_subtype);
            $order->save();
            $order = wc_get_order($order->get_id());
        }

        return $order;
    }

    protected function get_payment_info($ipn_info)
    {
        if (isset($ipn_info['PaymentType'])) {
            $payment_type = $ipn_info['PaymentType'];
            $payment_type = explode('_', $payment_type);
            if (1 == count($payment_type)) {
                $payment_type[] = '';
            }
            return $payment_type;
        }

        return false;
    }

    public function payment_complete($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [$this, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        $order = $this->set_transaction_info($order, $ipn_info);
        $order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
        $order->payment_complete();
    }

    public function payment_wait_atm($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [$this, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        switch($order->get_payment_method()) {
            case 'ry_ecpay_atm':
                $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_atm_BankCode', $ipn_info['BankCode']);
                $order->update_meta_data('_ecpay_atm_vAccount', $ipn_info['vAccount']);
                $order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
            case 'ry_ecpay_bnpl':
                $order->update_meta_data('_ecpay_bnpl_TradeNo', $ipn_info['BNPLTradeNo']);
                $order->update_meta_data('_ecpay_bnpl_Installment', $ipn_info['BNPLInstallment']);
                $order->payment_complete();
                break;
        }
    }

    public function payment_wait_cvs($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [$this, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            return;
        }

        switch($order->get_payment_method()) {
            case 'ry_ecpay_barcode':
                $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_barcode_Barcode1', $ipn_info['Barcode1']);
                $order->update_meta_data('_ecpay_barcode_Barcode2', $ipn_info['Barcode2']);
                $order->update_meta_data('_ecpay_barcode_Barcode3', $ipn_info['Barcode3']);
                $order->update_meta_data('_ecpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
            case 'ry_ecpay_cvs':
                $expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));
                $order->update_meta_data('_ecpay_cvs_PaymentNo', $ipn_info['PaymentNo']);
                $order->update_meta_data('_ecpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->update_status('on-hold');
                break;
        }
    }

    public function payment_failed($ipn_info, $order): void
    {
        remove_action('ry_ecpay_gateway_response', [$this, 'add_noaction_note'], 10, 2);

        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
            return;
        }

        $order->update_status('failed', sprintf(
            /* translators: %1$s: status message, %2$d status code */
            __('Payment failed: %1$s (%2$d)', 'ry-woocommerce-tools'),
            $this->get_status_msg($ipn_info),
            $this->get_status($ipn_info),
        ));
    }

    public function add_noaction_note($ipn_info, $order): void
    {
        RY_WT_WC_ECPay_Gateway::instance()->log('Unknow status', WC_Log_Levels::INFO, ['status' => $this->get_status($ipn_info), 'status_msg' => $this->get_status_msg($ipn_info)]);
        $order->add_order_note(sprintf(
            /* translators: %1$s: status message, %2$d status code */
            __('Payment unkonw status: %1$s (%2$d)', 'ry-woocommerce-tools'),
            $this->get_status_msg($ipn_info),
            $this->get_status($ipn_info),
        ));
    }
}
