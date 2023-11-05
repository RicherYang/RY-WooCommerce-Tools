<?php

class RY_WT_WC_SmilePay_Gateway_Api extends RY_WT_EC_SmilePay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp'
    ];
    protected $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp'
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
        RY_WT_WC_SmilePay_Gateway::instance()->log('Generating payment form by ' . $gateway->id . ' for #' . $order->get_order_number());

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
            'Roturl_status' => 'RY_SmilePay'
        ];
        if ($gateway->get_code_mode) {
            $args['Verify_key'] = $Verify_key;
        }

        $args = $this->add_type_info($args, $order, $gateway);
        RY_WT_WC_SmilePay_Gateway::instance()->log('Get code POST: ' . var_export($args, true));

        $order->update_meta_data('_smilepay_Data_id', $args['Data_id']);
        $order->save();

        if (!$gateway->get_code_mode) {
            if (RY_WT_WC_SmilePay_Gateway::instance()->testmode) {
                $url = $this->api_test_url['checkout'];
            } else {
                $url = $this->api_url['checkout'];
            }
            return $url . '?' . http_build_query($args, '', '&');
        }

        if (RY_WT_WC_SmilePay_Gateway::instance()->testmode) {
            $url = $this->api_test_url['api_checkout'];
        } else {
            $url = $this->api_url['api_checkout'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Get code failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 != $response_code) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Get code failed. Http code: ' . $response_code, 'error');
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        RY_WT_WC_SmilePay_Gateway::instance()->log('Get code result: ' . $body);

        $ipn_info = @simplexml_load_string($body);
        if (!$ipn_info) {
            RY_WT_WC_ECPay_Shipping::instance()->log('Get code failed. Parse result failed.', 'warning');
            return false;
        }

        RY_WT_WC_SmilePay_Gateway::instance()->log('Get code result data: ' . var_export($ipn_info, true));

        if ((string) $ipn_info->Status != '1') {
            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$d Error messade ID */
                __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                $ipn_info->Desc,
                $ipn_info->Status
            ));
            return false;
        }

        $order = $this->set_transaction_info($order, $ipn_info, $gateway::Payment_Type);

        switch ($gateway::Payment_Type) {
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

    protected function set_transaction_info($order, $ipn_info, $payment_type)
    {
        $transaction_ID = (string) $order->get_transaction_id();
        if ($transaction_ID == '' || !$order->is_paid() || $transaction_ID != $this->get_transaction_id($ipn_info)) {
            $order->set_transaction_id($this->get_transaction_id($ipn_info));
            $order->update_meta_data('_smilepay_payment_type', $payment_type);
            $order->save();
            $order = wc_get_order($order->get_id());
        }
        return $order;
    }
}
