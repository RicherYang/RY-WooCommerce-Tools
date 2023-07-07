<?php

class RY_NewebPay_Gateway_Response extends RY_NewebPay_Gateway_Api
{
    public static function init()
    {
        add_action('woocommerce_api_request', [__CLASS__, 'set_do_die']);
        add_action('woocommerce_api_ry_newebpay_gateway_return', [__CLASS__, 'callback_gateway_return']);
        add_action('woocommerce_api_ry_newebpay_callback', [__CLASS__, 'check_callback']);
        add_action('valid_newebpay_gateway_request', [__CLASS__, 'doing_callback']);
    }

    public static function callback_gateway_return()
    {
        self::set_not_do_die();
        self::check_callback();
        self::gateway_return();
    }

    public static function check_callback()
    {
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if (self::ipn_request_is_valid($ipn_info)) {
                do_action('valid_newebpay_gateway_request', $ipn_info);
            } else {
                self::die_error();
            }
        }
    }

    protected static function ipn_request_is_valid($ipn_info)
    {
        $info_value = self::get_tradeInfo_value($ipn_info);
        if ($info_value) {
            RY_NewebPay_Gateway::log('IPN request: ' . var_export($ipn_info, true));

            list($MerchantID, $HashKey, $HashIV) = RY_NewebPay_Gateway::get_newebpay_api_info();

            $info_sha_value = self::get_tradeSha_value($ipn_info);
            if ($info_sha_value == self::generate_hash_value($info_value, $HashKey, $HashIV)) {
                return true;
            } else {
                RY_NewebPay_Gateway::log('IPN request check failed. Response:' . $info_sha_value . ' Self:' . self::generate_hash_value($info_value, $HashKey, $HashIV), 'error');
                return false;
            }
        }
    }

    public static function doing_callback($ipn_info)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_NewebPay_Gateway::get_newebpay_api_info();

        $ipn_info = self::get_tradeInfo_value($ipn_info);
        $ipn_info = self::args_decrypt($ipn_info, $HashKey, $HashIV);
        $ipn_info = json_decode($ipn_info);
        RY_NewebPay_Gateway::log('IPN decrypt request: ' . var_export($ipn_info, true));

        $order_id = self::get_order_id($ipn_info, RY_WT::get_option('newebpay_gateway_order_prefix'));
        if ($order = wc_get_order($order_id)) {
            $payment_status = self::get_status($ipn_info);
            RY_NewebPay_Gateway::log('Found order #' . $order->get_id() . ' Payment status: ' . $payment_status);

            $transaction_id = (string) $order->get_transaction_id();
            if ($transaction_id == '' || $transaction_id != self::get_transaction_id($ipn_info)) {
                $payment_type = self::get_payment_type($ipn_info);
                $order->set_transaction_id(self::get_transaction_id($ipn_info));
                $order->update_meta_data('_newebpay_payment_type', $payment_type);
                $order->save();
                $order = wc_get_order($order_id);
            }

            if (method_exists(__CLASS__, 'payment_status_' . $payment_status)) {
                call_user_func([__CLASS__, 'payment_status_' . $payment_status], $order, $ipn_info->Result);
            } else {
                self::payment_status_unknow($order, $ipn_info->Result, $payment_status);
            }

            do_action('ry_newebpay_gateway_response_status_' . $payment_status, $ipn_info->Result, $order);
            do_action('ry_newebpay_gateway_response', $ipn_info->Result, $order);

            self::die_success();
        } else {
            RY_NewebPay_Gateway::log('Order not found', 'warning');
            self::die_error();
        }
    }

    protected static function payment_status_SUCCESS($order, $ipn_info)
    {
        if (isset($ipn_info->StoreCode)) {
            $order = wc_get_order($order);
            if ($order->get_meta('_shipping_cvs_store_ID') == '') {
                $order->set_shipping_company('');
                $order->set_shipping_address_2('');
                $order->set_shipping_city('');
                $order->set_shipping_state('');
                $order->set_shipping_postcode('');

                $order->set_shipping_last_name('');
                $order->set_shipping_first_name($ipn_info->CVSCOMName);
                $order->add_order_note(sprintf(
                    /* translators: 1: Store name 2: Store ID */
                    __('CVS store %1$s (%2$s)', 'ry-woocommerce-tools'),
                    $ipn_info->StoreName,
                    $ipn_info->StoreCode
                ));
                $order->update_meta_data('_shipping_cvs_store_ID', $ipn_info->StoreCode);
                $order->update_meta_data('_shipping_cvs_store_name', $ipn_info->StoreName);
                $order->update_meta_data('_shipping_cvs_store_address', $ipn_info->StoreAddr);
                $order->update_meta_data('_shipping_cvs_store_type', $ipn_info->StoreType);
                if (version_compare(WC_VERSION, '5.6.0', '<')) {
                    $order->update_meta_data('_shipping_phone', $ipn_info->CVSCOMPhone);
                } else {
                    $order->set_shipping_phone($ipn_info->CVSCOMPhone);
                }

                $order->set_shipping_address_1($ipn_info->StoreAddr);
                $order->save();

                $shipping_list = $order->get_meta('_newebpay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (!isset($shipping_list[$ipn_info->TradeNo])) {
                    $shipping_list[$ipn_info->TradeNo] = [];
                }
                $shipping_list[$ipn_info->TradeNo]['ID'] = $ipn_info->TradeNo;
                $shipping_list[$ipn_info->TradeNo]['Type'] = $ipn_info->StoreType;
                $shipping_list[$ipn_info->TradeNo]['PaymentNo'] = $ipn_info->TradeNo;
                $shipping_list[$ipn_info->TradeNo]['store_ID'] = $ipn_info->StoreCode;
                $shipping_list[$ipn_info->TradeNo]['create'] = (string) new WC_DateTime();
                $shipping_list[$ipn_info->TradeNo]['edit'] = (string) new WC_DateTime();
                $shipping_list[$ipn_info->TradeNo]['amount'] = $ipn_info->Amt;
                $shipping_list[$ipn_info->TradeNo]['IsCollection'] = $ipn_info->TradeType;

                $order->update_meta_data('_newebpay_shipping_info', $shipping_list);
                $order->save_meta_data();

                if ($ipn_info->TradeType == '1') {
                    if ($order->get_status() == 'pending') {
                        $order->update_status('processing');
                    }
                }
            }
        }

        $order = wc_get_order($order);
        if (!$order->is_paid()) {
            if (isset($ipn_info->PayTime)) {
                $order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
                $order->payment_complete();
            } elseif (isset($ipn_info->BankCode)) {
                $expireDate = new DateTime($ipn_info->ExpireDate . ' ' . $ipn_info->ExpireTime, new DateTimeZone('Asia/Taipei'));

                $order->update_meta_data('_newebpay_atm_BankCode', $ipn_info->BankCode);
                $order->update_meta_data('_newebpay_atm_vAccount', $ipn_info->CodeNo);
                $order->update_meta_data('_newebpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->save_meta_data();

                $order->update_status('on-hold');
            } elseif (isset($ipn_info->CodeNo)) {
                $expireDate = new DateTime($ipn_info->ExpireDate . ' ' . $ipn_info->ExpireTime, new DateTimeZone('Asia/Taipei'));

                $order->update_meta_data('_newebpay_cvs_PaymentNo', $ipn_info->CodeNo);
                $order->update_meta_data('_newebpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->save_meta_data();

                $order->update_status('on-hold');
            } elseif (isset($ipn_info->Barcode_1)) {
                $expireDate = new DateTime($ipn_info->ExpireDate . ' ' . $ipn_info->ExpireTime, new DateTimeZone('Asia/Taipei'));

                $order->update_meta_data('_newebpay_barcode_Barcode1', $ipn_info->Barcode_1);
                $order->update_meta_data('_newebpay_barcode_Barcode2', $ipn_info->Barcode_2);
                $order->update_meta_data('_newebpay_barcode_Barcode3', $ipn_info->Barcode_3);
                $order->update_meta_data('_newebpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
                $order->save_meta_data();

                $order->update_status('on-hold');
            }
        }
    }

    protected static function payment_status_unknow($order, $ipn_info, $payment_status)
    {
        RY_NewebPay_Gateway::log('Unknow status: ' . self::get_status($ipn_info) . '(' . self::get_status_msg($ipn_info) . ')');
        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'ry-woocommerce-tools'));
            $order->save();
        } else {
            $order->update_status('failed', sprintf(
                /* translators: 1: Error status code 2: Error status message */
                __('Payment failed: %1$s (%2$s)', 'ry-woocommerce-tools'),
                self::get_status($ipn_info),
                self::get_status_msg($ipn_info)
            ));
        }
    }
}
