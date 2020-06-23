<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_SmilePay_Gateway_Api extends RY_SmilePay
{
    public static $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp'
    ];
    public static $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp'
    ];

    public static function get_code($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $gateways = WC_Payment_Gateways::instance();
        $payment_gateways = $gateways->payment_gateways();
        $payment_method = $order->get_payment_method();
        if (!isset($payment_gateways[$payment_method])) {
            return false;
        }

        $gateway = $payment_gateways[$payment_method];
        RY_SmilePay_Gateway::log('Generating payment form by ' . $gateway->id . ' for #' . $order->get_order_number());

        $notify_url = WC()->api_request_url('ry_smilepay_callback', true);

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => self::get_item_name($order),
            'Pay_zg' => $gateway->payment_type,
            'Data_id' => self::generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Roturl' => $notify_url,
            'Roturl_status' => 'RY_SmilePay'
        ];
        if ($gateway->get_code_mode) {
            $args['Verify_key'] = $Verify_key;
        }

        $args = self::add_type_info($args, $order, $gateway);
        RY_SmilePay_Gateway::log('Get code POST: ' . var_export($args, true));

        $order->update_meta_data('_smilepay_Data_id', $args['Data_id']);
        $order->save_meta_data();

        if (!$gateway->get_code_mode) {
            if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'yes')) {
                $url = self::$api_test_url['checkout'];
            } else {
                $url = self::$api_url['checkout'];
            }
            return $url . '?' . http_build_query($args, '', '&');
        }

        if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'yes')) {
            $url = self::$api_test_url['api_checkout'];
        } else {
            $url = self::$api_url['api_checkout'];
        }

        $response = self::link_server($url, $args);
        if (is_wp_error($response)) {
            RY_SmilePay_Gateway::log('Get code failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 != $response_code) {
            RY_SmilePay_Gateway::log('Get code failed. Http code: ' . $response_code, 'error');
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        RY_SmilePay_Gateway::log('Get code result: ' . $body);

        $ipn_info = @simplexml_load_string($body);
        if (!$ipn_info) {
            RY_ECPay_Shipping::log('Get code failed. Parse result failed.', 'error');
            return false;
        }

        RY_SmilePay_Gateway::log('Get code result data: ' . var_export($ipn_info, true));

        if ((string) $ipn_info->Status != '1') {
            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$d Error messade ID */
                __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                $ipn_info->Desc,
                $ipn_info->Status
            ));
            return false;
        }

        $order = self::set_transaction_info($order, $ipn_info, $gateway->payment_type);

        switch ($gateway->payment_type) {
            case 2:
                $order->update_meta_data('_smilepay_atm_BankCode', (string) $ipn_info->AtmBankNo);
                $order->update_meta_data('_smilepay_atm_vAccount', (string) $ipn_info->AtmNo);
                $order->update_meta_data('_smilepay_atm_ExpireDate', (string) $ipn_info->PayEndDate);
                break;
            case 3:
                $order->update_meta_data('_smilepay_barcode_Barcode1', (string) $ipn_info->Barcode1);
                $order->update_meta_data('_smilepay_barcode_Barcode2', (string) $ipn_info->Barcode2);
                $order->update_meta_data('_smilepay_barcode_Barcode3', (string) $ipn_info->Barcode3);
                $order->update_meta_data('_smilepay_barcode_ExpireDate', (string) $ipn_info->PayEndDate);
                break;
            case 4:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $ipn_info->IbonNo);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $ipn_info->PayEndDate);
                break;
            case 6:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $ipn_info->FamiNO);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $ipn_info->PayEndDate);
                break;
        }
        $order->save_meta_data();
        $order->update_status('on-hold');

        return $order->get_checkout_order_received_url();
    }

    protected static function add_type_info($args, $order, $gateway)
    {
        switch ($gateway->payment_type) {
            case '2':
            case '3':
                $date = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $date->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                $args['Deadline_date'] = $date->format('Y/m/d');
                break;
            case '4':
            case '6':
                $date = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $date->add(new DateInterval('PT' . $gateway->expire_date . 'M'));
                $args['Deadline_date'] = $date->format('Y/m/d');
                $args['Deadline_time'] = $date->format('H:i:s');
                break;
        }
        return $args;
    }

    protected static function get_item_name($order)
    {
        $item_name = '';
        if (count($order->get_items())) {
            foreach ($order->get_items() as $item) {
                $item_name .= trim($item->get_name()) . '#';
                if (strlen($item_name) > 50) {
                    break;
                }
            }
        }
        $item_name = rtrim($item_name, '#');
        return $item_name;
    }

    protected static function set_transaction_info($order, $ipn_info, $payment_type)
    {
        $transaction_id = (string) $order->get_transaction_id();
        if ($transaction_id == '' || !$order->is_paid() || $transaction_id != self::get_transaction_id($ipn_info)) {
            $order->set_transaction_id(self::get_transaction_id($ipn_info));
            $order->update_meta_data('_smilepay_payment_type', $payment_type);
            $order->save();
            $order = wc_get_order($order->get_id());
        }
        return $order;
    }
}
