<?php
class RY_SmilePay_Shipping_Api extends RY_Abstract_Api_SmilePay
{
    public static $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos_test/mtmk_utf.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp'
    ];
    public static $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp'
    ];

    public static function get_csv_info($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        RY_SmilePay_Shipping::log('Generating csv for #' . $order->get_order_number());

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

        $item_name = self::get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => $item_name,
            'Pay_zg' => 52,
            'Data_id' => self::generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Pur_name' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_map_callback', true),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true)
        ];
        if (version_compare(WC_VERSION, '5.6.0', '<')) {
            $args['Mobile_number'] = $order->get_meta('_shipping_phone');
        } else {
            $args['Mobile_number'] = $order->get_shipping_phone();
        }

        if ($order->get_payment_method() == 'cod') {
            $args['Pay_zg'] = 51;
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_method = RY_SmilePay_Shipping::get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }
            $method_class = RY_SmilePay_Shipping::$support_methods[$shipping_method];
            $args['Pay_subzg'] = $method_class::$Type;
            break;
        }

        RY_SmilePay_Shipping::log('Get info POST: ' . var_export($args, true));

        if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'no')) {
            $url = self::$api_test_url['checkout'];
        } else {
            $url = self::$api_url['checkout'];
        }
        return $url . '?' . http_build_query($args, '', '&');
    }

    public static function get_csv_no($order_id, $cod = false)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        RY_SmilePay_Shipping::log('Generating csv for #' . $order->get_order_number());

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

        $item_name = self::get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Od_sob' => $item_name,
            'Pay_zg' => $cod ? 51 : 52,
            'Data_id' => self::generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')),
            'Amount' => (int) ceil($order->get_total()),
            'Pur_name' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_admin_map_callback', true),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
            'Logistics_store' => $order->get_meta('_shipping_cvs_store_ID') . '/' . $order->get_meta('_shipping_cvs_store_name') . '/' . $order->get_meta('_shipping_cvs_store_address')
        ];
        if (version_compare(WC_VERSION, '5.6.0', '<')) {
            $args['Mobile_number'] = $order->get_meta('_shipping_phone');
        } else {
            $args['Mobile_number'] = $order->get_shipping_phone();
        }

        foreach ($order->get_items('shipping') as $item) {
            $shipping_method = RY_SmilePay_Shipping::get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }
            $method_class = RY_SmilePay_Shipping::$support_methods[$shipping_method];
            $args['Pay_subzg'] = $method_class::$Type;
            break;
        }

        if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'no')) {
            $url = self::$api_test_url['checkout'];
        } else {
            $url = self::$api_url['checkout'];
        }

        wp_redirect($url . '?' . http_build_query($args, '', '&'));
        die();
    }

    public static function get_csv_no_cod($order_id)
    {
        self::get_csv_no($order_id, true);
    }

    public static function get_code_no($order_id, $get_smse_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        foreach ($shipping_list as $smse_id => $info) {
            if ($get_smse_id != $smse_id) {
                continue;
            }

            $args = [
                'Dcvc' => $Dcvc,
                'Verify_key' => $Verify_key,
                'smseid' => $info['ID'],
                'Pay_subzg' => $info['type'],
                'types' => 'Xml'
            ];

            RY_SmilePay_Shipping::log('Get code POST: ' . var_export($args, true));

            if ($info['IsCollection']) {
                if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'no')) {
                    $url = self::$api_test_url['pay'];
                } else {
                    $url = self::$api_url['pay'];
                }
            } else {
                if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'no')) {
                    $url = self::$api_test_url['unpay'];
                } else {
                    $url = self::$api_url['unpay'];
                }
            }

            $response = self::link_server($url, $args);
            if (is_wp_error($response)) {
                RY_SmilePay_Shipping::log('Get code failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if (200 != $response_code) {
                RY_SmilePay_Shipping::log('Get code failed. Http code: ' . $response_code, 'error');
                return false;
            }

            $body = wp_remote_retrieve_body($response);
            RY_SmilePay_Shipping::log('Get code result: ' . $body);

            $ipn_info = @simplexml_load_string($body);
            if (!$ipn_info) {
                RY_SmilePay_Shipping::log('Get code failed. Parse result failed.', 'error');
                return false;
            }

            RY_SmilePay_Shipping::log('Get code result data: ' . var_export($ipn_info, true));

            if ((string) $ipn_info->Status != '1') {
                $order->add_order_note(sprintf(
                    /* translators: %1$s Error messade, %2$d Error messade ID */
                    __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                    (string) $ipn_info->Desc,
                    (string) $ipn_info->Status
                ));
                return false;
            }

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            $shipping_list[$info['ID']]['PaymentNo'] = (string) $ipn_info->paymentno;
            $shipping_list[$info['ID']]['ValidationNo'] = (string) $ipn_info->validationno;
            $shipping_list[$info['ID']]['edit'] = (string) new WC_DateTime();
            $shipping_list[$info['ID']]['amount'] = (int) $ipn_info->Amount;
            $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
            $order->save_meta_data();
        }
    }

    public static function get_print_url($info_list, $multi = false)
    {
        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_SmilePay_Gateway::get_smilepay_api_info();

        $args = [
            'Dcvc' => $Dcvc,
            'Rvg2c' => $Rvg2c,
            'Verify_key' => $Verify_key
        ];
        if (!$multi) {
            $info_list = [$info_list];
        }

        $no_list = [];
        foreach ($info_list as $info) {
            $no_list[] = $info['PaymentNo'] . $info['ValidationNo'];
        }
        $no_list = array_filter($no_list);
        $args['Pay_subzg'] = $info_list[0]['type'];
        $args['PinCodes'] = implode(',', $no_list);

        if ('yes' === RY_WT::get_option('smilepay_gateway_testmode', 'no')) {
            $url = self::$api_test_url['print'];
        } else {
            $url = self::$api_url['print'];
        }
        return $url . '?' . http_build_query($args, '', '&');
    }
}
