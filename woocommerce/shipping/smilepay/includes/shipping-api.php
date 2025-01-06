<?php

class RY_WT_WC_SmilePay_Shipping_Api extends RY_WT_SmilePay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'code' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'cat' => 'http://ssl.smse.com.tw/api/ezcatGetTrackNum.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp',
        'cat_print' => 'https://ssl.smse.com.tw/api/ezcatPrintDelivery.asp',
    ];

    protected $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'code' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
        'pay' => 'https://ssl.smse.com.tw/api/C2CPayment.asp',
        'unpay' => 'https://ssl.smse.com.tw/api/C2CPaymentU.asp',
        'cat' => 'http://ssl.smse.com.tw/api/ezcatGetTrackNum.asp',
        'print' => 'https://ssl.smse.com.tw/api/C2C_MultiplePrint.asp',
        'cat_print' => 'https://ssl.smse.com.tw/api/ezcatPrintDelivery.asp',
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

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating csv for #' . $order->get_id(), WC_Log_Levels::INFO);

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
            'Mobile_number' => str_replace(['-', ' '], ' ', $order->get_shipping_phone()),
            'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
            'Roturl_status' => 'RY_SmilePay',
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_map_callback'),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
        ];

        if ('cod' === $order->get_payment_method()) {
            $args['Pay_zg'] = 51;
        }

        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($shipping_item);
            if (false === $shipping_method) {
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

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating csv for #' . $order->get_id(), WC_Log_Levels::INFO);

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
            'MapRoturl' => WC()->api_request_url('ry_smilepay_shipping_admin_callback'),
            'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
            'Logistics_store' => $order->get_meta('_shipping_cvs_store_ID'),
        ];

        if (true === $collection) {
            $args['Pay_zg'] = 51;
        }

        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($shipping_item);
            if (false === $shipping_method) {
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

    public function get_home_info($order_ID, $collection = false)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        $get_no_ID = [];
        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($shipping_item);
            if (false === $shipping_method) {
                continue;
            }

            $method_class = RY_WT_WC_SmilePay_Shipping::$support_methods[$shipping_method];
            list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

            $package_list = $this->get_shipping_package($order, $method_class, 'keep', null, 0);
            if (0 === count($package_list)) {
                continue;
            }

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }

            RY_WT_WC_SmilePay_Shipping::instance()->log('Generating home for #' . $order->get_id(), WC_Log_Levels::INFO);

            $item_name = $this->get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
            $item_name = mb_substr($item_name, 0, 20);

            $country = $order->get_shipping_country();
            $state = $order->get_shipping_state();
            $states = WC()->countries->get_states($country);
            $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

            $args = [
                'Dcvc' => $Dcvc,
                'Rvg2c' => $Rvg2c,
                'Verify_key' => $Verify_key,
                'Od_sob' => $item_name,
                'Pay_zg' => 82,
                'Pay_subzg' => $method_class::Shipping_Type,
                'Pur_name' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                'Mobile_number' => str_replace(['-', ' '], ' ', $order->get_shipping_phone()),
                'Address' => $full_state . $order->get_shipping_city() . $order->get_shipping_address_1() . $order->get_shipping_address_2(),
                'Roturl' => WC()->api_request_url('ry_smilepay_callback', true),
                'Roturl_status' => 'RY_SmilePay',
                'Logistics_Roturl' => WC()->api_request_url('ry_smilepay_shipping_callback', true),
            ];

            if (0 === count($shipping_list)) {
                if ('cod' === $order->get_payment_method()) {
                    $args['Pay_zg'] = 81;
                }
            }
            if (true === $collection) {
                $args['Pay_zg'] = 81;
            }
            if (81 === $args['Pay_zg']) {
                $total_amount = 0;
                foreach ($package_list as $package_info) {
                    $total_amount += $package_info['fee'];
                }
                if ($order->get_total() != $total_amount) {
                    $package_list[0]['fee'] += $order->get_total() - $total_amount;
                }
            }

            RY_WT_WC_SmilePay_Shipping::instance()->log('Home POST data', WC_Log_Levels::INFO, ['data' => $args]);

            if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                $url = $this->api_test_url['code'];
            } else {
                $url = $this->api_url['code'];
            }

            foreach ($package_list as $package_info) {
                $args['Data_id'] = $this->generate_trade_no($order->get_id(), RY_WT::get_option('smilepay_gateway_order_prefix')) . 'T' . $package_info['temp'];
                $args['Amount'] = (int) $package_info['price'];
                if (81 === $args['Pay_zg']) {
                    $args['Amount'] = (int) $package_info['fee'];
                }

                $response = $this->link_server($url, $args);
                if (is_wp_error($response)) {
                    RY_WT_WC_SmilePay_Shipping::instance()->log('Home POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
                    return false;
                }

                if (wp_remote_retrieve_response_code($response) != '200') {
                    RY_WT_WC_SmilePay_Shipping::instance()->log('Home POST HTTP status error', WC_Log_Levels::ERROR, ['code' => $response['response']['code']]);
                    return false;
                }

                $result = @simplexml_load_string(wp_remote_retrieve_body($response));
                if (!$result) {
                    RY_WT_WC_SmilePay_Shipping::instance()->log('Home POST result parse failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
                    return false;
                }

                RY_WT_WC_SmilePay_Shipping::instance()->log('Home POST result', WC_Log_Levels::INFO, ['data' => $result]);

                if ((string) $result->Status != '1') {
                    $order->add_order_note(sprintf(
                        /* translators: %1$s Error messade, %2$d Error messade ID */
                        __('Get Smilepay no error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                        (string) $result->Desc,
                        (string) $result->Status,
                    ));
                    return false;
                }

                $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                $transaction_ID = $this->get_transaction_id($result);
                if ($transaction_ID) {
                    $get_no_ID[] = $transaction_ID;
                    if (!isset($shipping_list[$transaction_ID])) {
                        if (RY_WT::get_option('smilepay_shipping_box_size', '0') == '0') {
                            if ($package_info['size'] > 0) {
                                $package_info['size'] = wc_get_dimension($package_info['size'], 'cm');
                                if ($package_info['size'] <= 60) {
                                    $spec = '0001';
                                } elseif ($package_info['size'] <= 90) {
                                    $spec = '0002';
                                } elseif ($package_info['size'] <= 120) {
                                    $spec = '0003';
                                } else {
                                    $spec = '0004';
                                }
                            } else {
                                $spec = '0001';
                            }
                        } else {
                            $spec = '000' . RY_WT::get_option('smilepay_shipping_box_size', '0');
                        }
                        $shipping_list[$transaction_ID] = [
                            'spec' => $spec,
                            'temp' => $package_info['temp'],
                        ];
                    }
                    $shipping_list[$transaction_ID]['ID'] = $transaction_ID;
                    $shipping_list[$transaction_ID]['LogisticsType'] = 'HOME';
                    $shipping_list[$transaction_ID]['amount'] = (int) $result->Amount;
                    $shipping_list[$transaction_ID]['IsCollection'] = 81 === $args['Pay_zg'] ? 1 : 0;
                    $shipping_list[$transaction_ID]['BookingNote'] = '';
                    $shipping_list[$transaction_ID]['type'] = $args['Pay_subzg'];
                    $shipping_list[$transaction_ID]['status'] = $this->get_status($result);
                    $shipping_list[$transaction_ID]['create'] = (string) new WC_DateTime();
                    $shipping_list[$transaction_ID]['edit'] = (string) new WC_DateTime();
                    $order->update_meta_data('_smilepay_shipping_info', $shipping_list);
                    $order->save();
                }
            }
            break;
        }

        foreach ($get_no_ID as $smse_ID) {
            $this->get_info_no($order->get_id(), $smse_ID);
        }
    }

    public function get_info_no($order_ID, $get_smse_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        RY_WT_WC_SmilePay_Shipping::instance()->log('Generating no for #' . $order->get_id(), WC_Log_Levels::INFO);

        list($Dcvc, $Rvg2c, $Verify_key, $Rot_check) = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
        if (!is_array($shipping_list)) {
            $shipping_list = [];
        }
        foreach ($shipping_list as $smse_ID => $info) {
            if ($get_smse_ID != $smse_ID) {
                continue;
            }

            $args = [
                'Dcvc' => $Dcvc,
                'Verify_key' => $Verify_key,
                'smseid' => $info['ID'],
            ];
            if ('CVS' === $info['LogisticsType']) {
                $args['Pay_subzg'] = $info['type'];
                $args['types'] = 'Xml';
            }
            if ('HOME' === $info['LogisticsType']) {
                $args['package_size'] = $info['spec'];
                $args['temperature'] = '000' . $info['temp'];
                $date = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $date->add(new DateInterval('P' . RY_WT::get_option('smilepay_shipping_tcat_delivery_date', '1') . 'D'));
                if (7 == $date->format('N')) { // 星期日
                    $date->add(new DateInterval('P1D'));
                }
                $args['delivery_date'] = $date->format('Y-m-d');
                $args['delivery_timezone'] = '4';
            }

            RY_WT_WC_SmilePay_Shipping::instance()->log('No POST data', WC_Log_Levels::INFO, ['data' => $args]);

            if ('CVS' === $info['LogisticsType']) {
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
            } else {
                if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                    $url = $this->api_test_url['cat'];
                } else {
                    $url = $this->api_url['cat'];
                }
            }

            $response = $this->link_server($url, $args);
            if (is_wp_error($response)) {
                RY_WT_WC_SmilePay_Shipping::instance()->log('No POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
                return false;
            }

            if (wp_remote_retrieve_response_code($response) != '200') {
                RY_WT_WC_SmilePay_Shipping::instance()->log('No POST HTTP status error', WC_Log_Levels::ERROR, ['code' => $response['response']['code']]);
                return false;
            }

            $result = @simplexml_load_string(wp_remote_retrieve_body($response));
            if (!$result) {
                RY_WT_WC_SmilePay_Shipping::instance()->log('No POST result parse failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
                return false;
            }

            RY_WT_WC_SmilePay_Shipping::instance()->log('No POST result', WC_Log_Levels::INFO, ['data' => $result]);

            if ((string) $result->Status != '1') {
                $order->add_order_note(sprintf(
                    /* translators: %1$s Error messade, %2$d Error messade ID */
                    __('Get Smilepay info no error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                    (string) $result->Desc,
                    (string) $result->Status,
                ));
                return false;
            }

            $shipping_list = $order->get_meta('_smilepay_shipping_info', true);
            $shipping_list[$info['ID']]['PaymentNo'] = (string) $result->paymentno;
            $shipping_list[$info['ID']]['ValidationNo'] = (string) $result->validationno;
            $shipping_list[$info['ID']]['BookingNote'] = (string) $result->TrackNum;
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
        ];

        $info_list = array_filter($info_list);
        if ('TCAT' === $print_type) {
            $args['Smseid'] = implode(',', $info_list);
            $args['print_format'] = RY_WT::get_option('smilepay_shipping_tcat_print_format', '2');
            if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                $url = $this->api_test_url['cat_print'];
            } else {
                $url = $this->api_url['cat_print'];
            }
        } else {
            $args['Pay_subzg'] = $print_type;
            $args['PinCodes'] = implode(',', $info_list);
            if (RY_WT_WC_SmilePay_Gateway::instance()->is_testmode()) {
                $url = $this->api_test_url['print'];
            } else {
                $url = $this->api_url['print'];
            }
        }

        wp_redirect($url . '?' . http_build_query($args, '', '&'));
    }
}
