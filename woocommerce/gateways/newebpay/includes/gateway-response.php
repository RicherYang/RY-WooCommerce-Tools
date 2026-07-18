<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_NewebPay_Gateway_Response extends RY_WT_NewebPay_Api
{
    private static ?self $_instance = null;

    private bool $only_success = false;

    public static function instance(): RY_WT_WC_NewebPay_Gateway_Response
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
        add_action('woocommerce_api_ry_newebpay_gateway_return', [$this, 'callback_gateway_return']);
        add_action('woocommerce_api_ry_newebpay_callback', [$this, 'check_callback']);
        add_action('valid_newebpay_gateway_request', [$this, 'doing_callback']);
    }

    public function callback_gateway_return()
    {
        $this->only_success = true;
        $this->set_not_do_die();
        $this->check_callback();
        $this->gateway_return();
    }

    public function check_callback()
    {
        if (is_array($_POST) && !empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if ($this->ipn_request_is_valid($ipn_info)) {
                do_action('valid_newebpay_gateway_request', $ipn_info);
            } else {
                $this->die_error();
            }
        }
    }

    protected function ipn_request_is_valid(array $ipn_info): bool
    {
        $check_value = $this->get_hash_value($ipn_info);
        if ($check_value) {
            RY_WT_WC_NewebPay_Gateway::instance()->log('IPN request', WC_Log_Levels::INFO, ['data' => $ipn_info]);
            $api_info = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();

            $info_value = $this->get_info_value($ipn_info);
            $ipn_info_check_value = $this->generate_hash_value($info_value, $api_info['HashKey'], $api_info['HashIV']);
            if ($check_value === $ipn_info_check_value) {
                return true;
            }
            RY_WT_WC_NewebPay_Gateway::instance()->log('IPN request check failed', WC_Log_Levels::ERROR, ['response' => $check_value, 'self' => $ipn_info_check_value]);
        }

        return false;
    }

    public function doing_callback($ipn_info)
    {
        $api_info = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();

        $info_value = $this->get_info_value($ipn_info);
        $info_value = $this->args_decrypt($info_value, $api_info['HashKey'], $api_info['HashIV']);
        $info_value = json_decode($info_value);
        RY_WT_WC_NewebPay_Gateway::instance()->log('IPN request decrypt', WC_Log_Levels::INFO, ['data' => $info_value]);

        $order_ID = $this->get_order_id($info_value, $api_info['prefix']);
        if ($order = wc_get_order($order_ID)) {
            $transaction_ID = (string) $order->get_transaction_id();
            if ($transaction_ID === '' || $transaction_ID != $this->get_transaction_id($info_value)) {
                $payment_type = $this->get_payment_type($info_value);
                $order->set_transaction_id($this->get_transaction_id($info_value));
                $order->update_meta_data('_newebpay_payment_type', $payment_type);
                $order->save();
                $order = wc_get_order($order_ID);
            }

            $payment_status = $this->get_status($info_value);
            RY_WT_WC_NewebPay_Gateway::instance()->log('Found #' . $order->get_id() . ' Payment status: ' . $payment_status, WC_Log_Levels::INFO);

            if ($this->only_success) {
                if (method_exists($this, 'payment_status_' . $payment_status)) {
                    call_user_func([$this, 'payment_status_' . $payment_status], $order, $info_value->Result);
                }
                return;
            }

            if (method_exists($this, 'payment_status_' . $payment_status)) {
                call_user_func([$this, 'payment_status_' . $payment_status], $order, $info_value->Result);
            } else {
                $this->payment_status_unknow($order, $info_value->Result);
            }

            do_action('ry_newebpay_gateway_response_status_' . $payment_status, $info_value->Result, $order);
            do_action('ry_newebpay_gateway_response', $info_value->Result, $order);

            $this->die_success();
        } else {
            RY_WT_WC_NewebPay_Gateway::instance()->log('Order not found', WC_Log_Levels::WARNING);
            $this->die_error();
        }
    }

    protected function payment_status_SUCCESS($order, $info_value)
    {
        if ($order->is_paid()) {
            RY_WT_WC_NewebPay_Gateway::instance()->log('Payment action with order #' . $order->get_id() . ' status: ' . $order->get_status(), WC_Log_Levels::INFO);
            return;
        }

        if (isset($info_value->StoreCode)) {
            if ($order->get_meta('_shipping_cvs_store_ID') == '') {
                $order->set_shipping_company('');
                $order->set_shipping_address_2('');
                $order->set_shipping_city('');
                $order->set_shipping_state('');
                $order->set_shipping_postcode('');

                $order->set_shipping_last_name('');
                $order->set_shipping_first_name($info_value->CVSCOMName);
                $order->add_order_note(sprintf(
                    /* translators: 1: Store name 2: Store ID */
                    __('CVS store %1$s (%2$s)', 'ry-woocommerce-tools'),
                    $info_value->StoreName,
                    $info_value->StoreCode,
                ));

                $order->update_meta_data('_shipping_cvs_store_ID', $info_value->StoreCode);
                $order->update_meta_data('_shipping_cvs_store_name', $info_value->StoreName);
                $order->update_meta_data('_shipping_cvs_store_address', $info_value->StoreAddr);
                $order->update_meta_data('_shipping_cvs_store_type', $info_value->StoreType);
                $order->set_shipping_phone($info_value->CVSCOMPhone);

                $order->set_shipping_address_1($info_value->StoreAddr);
                $order->save();

                $shipping_list = $order->get_meta('_newebpay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (!isset($shipping_list[$info_value->TradeNo])) {
                    $shipping_list[$info_value->TradeNo] = [];
                }
                $shipping_list[$info_value->TradeNo]['ID'] = $info_value->TradeNo;
                $shipping_list[$info_value->TradeNo]['LogisticsType'] = 'CVS';
                $shipping_list[$info_value->TradeNo]['Type'] = $info_value->StoreType;
                $shipping_list[$info_value->TradeNo]['PaymentNo'] = $info_value->LgsNo;
                $shipping_list[$info_value->TradeNo]['store_ID'] = $info_value->StoreCode;
                $shipping_list[$info_value->TradeNo]['create'] = (string) new WC_DateTime();
                $shipping_list[$info_value->TradeNo]['edit'] = (string) new WC_DateTime();
                $shipping_list[$info_value->TradeNo]['amount'] = $info_value->Amt;
                $shipping_list[$info_value->TradeNo]['IsCollection'] = $info_value->TradeType;

                $order->update_meta_data('_newebpay_shipping_info', $shipping_list);
                $order->save();

                if ($info_value->TradeType == '1') {
                    if ($order->get_status() == 'pending') {
                        $order->update_status('processing');
                    }
                }
                $order = wc_get_order($order->get_id());
            }
        }

        if (isset($info_value->PayTime) && !empty($info_value->PayTime)) {
            $order->add_order_note(__('NewebPay payment completed', 'ry-woocommerce-tools'));
            if (isset($info_value->Inst)) {
                $installment = (int) $info_value->Inst;
                if ($installment > 1) {
                    $order->add_order_note(sprintf(
                        /* translators: %d number of periods */
                        __('Credit installment to %d', 'ry-woocommerce-tools'),
                        $installment,
                    ));
                }
            }

            $order->payment_complete();
        } else {
            switch ($order->get_payment_method()) {
                case 'ry_newebpay_atm':
                    $expireDate = new DateTime($info_value->ExpireDate . ' ' . $info_value->ExpireTime, new DateTimeZone('Asia/Taipei'));
                    $order->update_meta_data('_newebpay_atm_BankCode', $info_value->BankCode);
                    $order->update_meta_data('_newebpay_atm_vAccount', $info_value->CodeNo);
                    $order->update_meta_data('_newebpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                    $order->update_status('on-hold');
                    break;
                case 'ry_newebpay_cvs':
                    $expireDate = new DateTime($info_value->ExpireDate . ' ' . $info_value->ExpireTime, new DateTimeZone('Asia/Taipei'));
                    $order->update_meta_data('_newebpay_cvs_PaymentNo', $info_value->CodeNo);
                    $order->update_meta_data('_newebpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                    $order->update_status('on-hold');
                    break;
                case 'ry_newebpay_barcode':
                    $expireDate = new DateTime($info_value->ExpireDate . ' ' . $info_value->ExpireTime, new DateTimeZone('Asia/Taipei'));
                    $order->update_meta_data('_newebpay_barcode_Barcode1', $info_value->Barcode_1);
                    $order->update_meta_data('_newebpay_barcode_Barcode2', $info_value->Barcode_2);
                    $order->update_meta_data('_newebpay_barcode_Barcode3', $info_value->Barcode_3);
                    $order->update_meta_data('_newebpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
                    $order->update_status('on-hold');
                    break;
            }
        }
    }

    protected function payment_status_unknow($order, $info_value)
    {
        RY_WT_WC_NewebPay_Gateway::instance()->log('Unknow status', WC_Log_Levels::INFO, ['status' => $this->get_status($info_value), 'status_msg' => $this->get_status_msg($info_value)]);
        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
        } else {
            $order->update_status('failed', sprintf(
                /* translators: 1: Error status code 2: Error status message */
                __('Payment failed: %1$s (%2$s)', 'ry-woocommerce-tools'),
                $this->get_status($info_value),
                $this->get_status_msg($info_value),
            ));
        }
    }
}
