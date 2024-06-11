<?php

class RY_WT_WC_SmilePay_Gateway_Api extends RY_WT_EC_SmilePay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
    ];
    protected $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
    ];

    public static function instance(): RY_WT_WC_SmilePay_Gateway_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkout_form($order)
    {
        $get_shipping = false;
        $items_shipping = $order->get_items('shipping');
        $items_shipping = array_shift($items_shipping);
        if ($items_shipping) {
            if (class_exists('RY_WT_WC_SmilePay_Shipping')) {
                if (false !== RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($items_shipping)) {
                    $get_shipping = true;
                }
            }
        }

        $this->submit_sctipt('$.ajax({
            type: "GET",
            url: wc_checkout_params.ajax_url,
            data: {
                action: "' . ($get_shipping ? 'RY_SmilePay_shipping_getcode' : 'RY_SmilePay_getcode') . '",
                id: ' . $order->get_id() . '
            },
            dataType: "text",
            success: function(result) {
                window.location = result;
            }
        });', $order);

        do_action('ry_smilepay_gateway_checkout', $order);
    }

    public function get_code($order_ID)
    {
        $order = wc_get_order($order_ID);
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
        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('payment_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 40);

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => $item_name,
            'Pay_zg' => $gateway::Payment_Type,
            'Data_id' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
        ];
        if ($gateway->get_code_mode) {
            $args['Verify_key'] = $Verify_key;
        }

        $args = $this->add_type_info($args, $order, $gateway);
        RY_WT_WC_SmilePay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        $order->update_meta_data('_smilepay_Data_id', $args['Data_id']);
        $order->save();

        if (!$gateway->get_code_mode) {
            if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                $url = $this->api_test_url['checkout'];
            } else {
                $url = $this->api_url['checkout'];
            }
            return $url . '?' . http_build_query($args, '', '&');
        }

        if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['api_checkout'];
        } else {
            $url = $this->api_url['api_checkout'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            return false;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment POST HTTP status error', WC_Log_Levels::ERROR, ['code' => wp_remote_retrieve_response_code($response)]);
            return false;
        }

        $result = @simplexml_load_string(wp_remote_retrieve_body($response));
        if (!$result) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment code POST result parse failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
            return false;
        }

        RY_WT_WC_SmilePay_Gateway::instance()->log('Shipping code POST result', WC_Log_Levels::INFO, ['data' => $result]);

        if ((string) $result->Status != '1') {
            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$d Error messade ID */
                __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                $result->Desc,
                $result->Status,
            ));
            return false;
        }

        $order = $this->set_transaction_info($order, $result, $gateway::Payment_Type);

        switch ($gateway::Payment_Type) {
            case 2:
                $order->update_meta_data('_smilepay_atm_BankCode', (string) $result->AtmBankNo);
                $order->update_meta_data('_smilepay_atm_vAccount', (string) $result->AtmNo);
                $order->update_meta_data('_smilepay_atm_ExpireDate', (string) $result->PayEndDate);
                break;
            case 3:
                $order->update_meta_data('_smilepay_barcode_Barcode1', (string) $result->Barcode1);
                $order->update_meta_data('_smilepay_barcode_Barcode2', (string) $result->Barcode2);
                $order->update_meta_data('_smilepay_barcode_Barcode3', (string) $result->Barcode3);
                $order->update_meta_data('_smilepay_barcode_ExpireDate', (string) $result->PayEndDate);
                break;
            case 4:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $result->IbonNo);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $result->PayEndDate);
                break;
            case 6:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $result->FamiNO);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $result->PayEndDate);
                break;
        }
        $order->save();
        $order->update_status('on-hold');

        return $order->get_checkout_order_received_url();
    }

    protected function add_type_info($args, $order, $gateway)
    {
        switch ($args['Pay_zg']) {
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

    protected function set_transaction_info($order, $result, $payment_type)
    {
        $transaction_ID = (string) $order->get_transaction_id();
        if ($transaction_ID == '' || !$order->is_paid() || $transaction_ID != $this->get_transaction_id($result)) {
            $order->set_transaction_id($this->get_transaction_id($result));
            $order->update_meta_data('_smilepay_payment_type', $payment_type);
            $order->save();
            $order = wc_get_order($order->get_id());
        }
        return $order;
    }
}
