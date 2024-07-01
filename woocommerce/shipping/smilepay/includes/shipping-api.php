<?php

class RY_WT_WC_SmilePay_Shipping_Api extends RY_WT_SmilePay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp',
    ];
    protected $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp',
    ];

    public static function instance(): RY_WT_WC_SmilePay_Shipping_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function get_csv_info($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating shipping for #' . $order->get_id(), WC_Log_Levels::INFO);

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => $item_name,
            'Pay_zg' => 52,
            'Data_id' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Pur_name' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
            'Mobile_number' => $order->get_shipping_phone(),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_map_callback', true),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
        ];

        if ($order->get_payment_method() == 'cod') {
            $args['Pay_zg'] = 51;
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }

            $method_class = RY_WT_WC_SmilePay_Shipping::$support_methods[$shipping_method];
            $args['Pay_subzg'] = $method_class::Shipping_Type;
            break;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping POST data', WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        return $url . '?' . http_build_query($args, '', '&');
    }

    public function get_admin_csv_info($order_ID, $collection = false)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating shipping for #' . $order->get_id(), WC_Log_Levels::INFO);

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => $item_name,
            'Pay_zg' => 52,
            'Data_id' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Pur_name' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
            'Mobile_number' => $order->get_shipping_phone(),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_admin_callback', true),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
            'Logistics_store' => $order->get_meta('_shipping_cvs_store_ID'),
        ];

        if (true === $collection) {
            $args['Pay_zg'] = 51;
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }

            $method_class = RY_WT_WC_SmilePay_Shipping::$support_methods[$shipping_method];
            $args['Pay_subzg'] = $method_class::Shipping_Type;
            break;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping POST data', WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        return $url . '?' . http_build_query($args, '', '&');
    }

    public function get_code_no($order_ID, $get_smse_id)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating shipping code for #' . $order->get_id(), WC_Log_Levels::INFO);

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        foreach ($shipping_list as $smse_ID => $info) {
            if ($get_smse_id != $smse_ID) {
                continue;
            }

            $args = [
                'Dcvc' => $Dcvc,
                'Verify_key' => $Verify_key,
                'smseid' => $info['ID'],
                'Pay_subzg' => $info['type'],
                'types' => 'Xml',
            ];

            RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping code POST data', WC_Log_Levels::INFO, ['data' => $args]);

            if ($info['IsCollection']) {
                if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                    $url = $this->api_test_url['pay'];
                } else {
                    $url = $this->api_url['pay'];
                }
            } else {
                if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                    $url = $this->api_test_url['unpay'];
                } else {
                    $url = $this->api_url['unpay'];
                }
            }

            $response = $this->link_server($url, $args);
            if (is_wp_error($response)) {
                RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping code POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
                return false;
            }

            if (wp_remote_retrieve_response_code($response) != '200') {
                RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping code POST HTTP status error', WC_Log_Levels::ERROR, ['code' => $response['response']['code']]);
                return false;
            }

            $result = @simplexml_load_string(wp_remote_retrieve_body($response));
            if (!$result) {
                RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping code POST result parse failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
                return false;
            }

            RY_WT_WC_SmilePay_Shipping::instance()->log('Shipping code POST result', WC_Log_Levels::INFO, ['data' => $result]);

            if ((string) $result->Status != '1') {
                $order->add_order_note(sprintf(
                    /* translators: %1$s Error messade, %2$d Error messade ID */
                    __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                    (string) $result->Desc,
                    (string) $result->Status,
                ));
                return false;
            }

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            $shipping_list[$info['ID']]['PaymentNo'] = (string) $result->paymentno;
            $shipping_list[$info['ID']]['ValidationNo'] = (string) $result->validationno;
            $shipping_list[$info['ID']]['edit'] = (string) new WC_DateTime();
            $shipping_list[$info['ID']]['amount'] = (int) $result->Amount;
            $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
            $order->save();
        }
    }

    public function get_print_url($info_list, $print_type)
    {
        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Verify_key' => $Verify_key,
            'Pay_subzg' => $print_type,
        ];

        $info_list = array_filter($info_list);
        $args['PinCodes'] = implode(',', $info_list);

        if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['print'];
        } else {
            $url = $this->api_url['print'];
        }

        wp_redirect($url . '?' . http_build_query($args, '', '&'));
    }
}
