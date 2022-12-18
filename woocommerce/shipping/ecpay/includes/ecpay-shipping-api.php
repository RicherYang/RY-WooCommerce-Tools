<?php
class RY_ECPay_Shipping_Api extends RY_Abstract_Api_ECPay
{
    public static $api_test_url = [
        'map' => 'https://logistics-stage.ecpay.com.tw/Express/map',
        'create' => 'https://logistics-stage.ecpay.com.tw/Express/Create',
        'print' => 'https://logistics-stage.ecpay.com.tw/Express/v2/PrintTradeDocument'
    ];
    public static $api_url = [
        'map' => 'https://logistics.ecpay.com.tw/Express/map',
        'create' => 'https://logistics.ecpay.com.tw/Express/Create',
        'print' => 'https://logistics.ecpay.com.tw/Express/v2/PrintTradeDocument'
    ];

    public static function get_map_post_url()
    {
        if (RY_ECPay_Shipping::$testmode) {
            return self::$api_test_url['map'];
        } else {
            return self::$api_url['map'];
        }
    }

    public static function get_code($order_id, $collection = false, $for_temp = null)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $item_name = self::get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);

        foreach ($order->get_items('shipping') as $item) {
            $shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }

            $method_class = RY_ECPay_Shipping::$support_methods[$shipping_method];
            list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

            $temp_list = [];
            foreach ($order->get_items('line_item') as $item) {
                $temp = $item->get_product()->get_meta('_ry_shipping_temp', true);
                if (empty($temp) && $item->get_product()->get_type() == 'variation') {
                    $parent_product = wc_get_product($item->get_product()->get_parent_id());
                    $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                }
                $temp = in_array($temp, $method_class::$support_temp) ? $temp : '1';
                if ($for_temp !== null && $temp != $for_temp) {
                    continue;
                }
                if (!isset($temp_list[$temp])) {
                    $temp_list[$temp] = [
                        'price' => 0,
                        'weight' => 0,
                        'size' => 0
                    ];
                }
                $temp_list[$temp]['price'] += $item->get_subtotal();
                $weight = (float) $item->get_product()->get_weight();
                if ($weight == 0) {
                    $weight = (float) RY_WT::get_option('ecpay_shipping_product_weight', 0);
                }
                $temp_list[$temp]['weight'] += $weight * $item->get_quantity();
                $size = (float) $item->get_product()->get_length() + (float) $item->get_product()->get_width() + (float) $item->get_product()->get_height();
                if ($size > 0) {
                    $temp_list[$temp]['size'] = max($size, $temp_list[$temp]['size']);
                }
            }

            if (count($temp_list) == 0) {
                continue;
            }

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }

            $notify_url = WC()->api_request_url('ry_ecpay_shipping_callback', true);

            RY_ECPay_Shipping::log('Generating shipping for order #' . $order->get_order_number());

            $args = [
                'MerchantID' => $MerchantID,
                'LogisticsType' => $method_class::$LogisticsType,
                'LogisticsSubType' => $method_class::$LogisticsSubType,
                'GoodsName' => $item_name,
                'SenderName' => RY_WT::get_option('ecpay_shipping_sender_name'),
                'SenderPhone' => RY_WT::get_option('ecpay_shipping_sender_phone'),
                'SenderCellPhone' => RY_WT::get_option('ecpay_shipping_sender_cellphone'),
                'ReceiverName' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                'ReceiverStoreID' => '',
                'ServerReplyURL' => $notify_url,
                'LogisticsC2CReplyURL' => $notify_url,
            ];
            if (version_compare(WC_VERSION, '5.6.0', '<')) {
                $args['ReceiverCellPhone'] = $order->get_meta('_shipping_phone');
            } else {
                $args['ReceiverCellPhone'] = $order->get_shipping_phone();
            }

            if ('yes' === RY_WT::get_option('ecpay_shipping_cleanup_receiver_name', 'no')) {
                $args['ReceiverName'] = preg_replace('/[^a-zA-Z\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', '', $args['ReceiverName']);
                if (preg_match('/^[a-zA-z]+$/', $args['ReceiverName'])) {
                    $args['ReceiverName'] = mb_substr($args['ReceiverName'], 0, 10);
                } else {
                    $args['ReceiverName'] = preg_replace('/[a-zA-Z]/', '', $args['ReceiverName']);
                    $args['ReceiverName'] = mb_substr($args['ReceiverName'], 0, 4);
                }
            }

            if ($args['LogisticsType'] == 'CVS') {
                $args['LogisticsSubType'] .= ('C2C' == $CVS_type) ? 'C2C' : '';
            }

            if (count($shipping_list) == 0) {
                if ($order->get_payment_method() == 'cod') {
                    $args['IsCollection'] = 'Y';
                    $args['CollectionAmount'] = $order->get_total();
                } else {
                    $args['IsCollection'] = 'N';
                    $args['CollectionAmount'] = 0;
                }
            }
            if ($collection == true) {
                $args['IsCollection'] = 'Y';
                $args['CollectionAmount'] = $order->get_total();
            }

            if ($method_class::$LogisticsType == 'CVS') {
                $args['ReceiverStoreID'] = $order->get_meta('_shipping_cvs_store_ID');
                $temp_list['1']['price'] = $order->get_total();
            }

            if ($method_class::$LogisticsType == 'Home') {
                $country = $order->get_shipping_country();

                $state = $order->get_shipping_state();
                $states = WC()->countries->get_states($country);
                $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

                $args['SenderZipCode'] = RY_WT::get_option('ecpay_shipping_sender_zipcode');
                $args['SenderAddress'] = RY_WT::get_option('ecpay_shipping_sender_address');
                $args['ReceiverZipCode'] = $order->get_shipping_postcode();
                $args['ReceiverAddress'] = $full_state . $order->get_shipping_city() . $order->get_shipping_address_1() . $order->get_shipping_address_2();
                $args['Specification'] = '000' . RY_WT::get_option('ecpay_shipping_box_size');
                $args['Distance'] = '00';

                $args['ScheduledPickupTime'] = RY_WT::get_option('ecpay_shipping_pickup_time');
                $args['ScheduledDeliveryTime'] = '4';
            }

            if (RY_ECPay_Shipping::$testmode) {
                $post_url = self::$api_test_url['create'];
            } else {
                $post_url = self::$api_url['create'];
            }

            foreach ($temp_list as $temp => $temp_info) {
                $create_datetime = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $args['MerchantTradeDate'] = $create_datetime->format('Y/m/d H:i:s');
                $args['MerchantTradeNo'] = self::generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_shipping_order_prefix'));

                $args['GoodsAmount'] = (int) $temp_info['price'];
                if ($method_class::$LogisticsType == 'Home') {
                    if ($temp_info['weight'] > 0) {
                        $args['GoodsWeight'] = wc_get_weight($temp_info['weight'], 'kg');
                    }
                    if ($args['Specification'] == '0000') {
                        if ($temp_info['size'] > 0) {
                            $temp_info['size'] = wc_get_dimension($temp_info['size'], 'cm');
                            if ($temp_info['size'] <= 60) {
                                $args['Specification'] = '0001';
                            } elseif ($temp_info['size'] <= 90) {
                                $args['Specification'] = '0002';
                            } elseif ($temp_info['size'] <= 120) {
                                $args['Specification'] = '0003';
                            } else {
                                $args['Specification'] = '0004';
                            }
                        } else {
                            $args['Specification'] = '0001';
                        }
                    }
                    $args['Temperature'] = '000' . $temp;
                    $args['MerchantTradeNo'] = substr($args['MerchantTradeNo'], 0, 18) . 'T' . $temp;
                }

                $args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
                RY_ECPay_Shipping::log('Shipping POST: ' . var_export($args, true));

                $response = self::link_server($post_url, $args);
                if (is_wp_error($response)) {
                    RY_ECPay_Shipping::log('Shipping failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
                    continue;
                }

                if ($response['response']['code'] != '200') {
                    RY_ECPay_Shipping::log('Shipping failed. Http code: ' . $response['response']['code'], 'error');
                    continue;
                }

                RY_ECPay_Shipping::log('Shipping request result: ' . $response['body']);
                $body = explode('|', $response['body']);
                if (count($body) != 2) {
                    RY_ECPay_Shipping::log('Shipping failed. Explode result failed.', 'warning');
                    continue;
                }

                if ($body[0] != '1') {
                    $order->add_order_note(sprintf(
                        /* translators: %s Error messade */
                        __('Get shipping code error: %s', 'ry-woocommerce-tools'),
                        $body[1]
                    ));
                    continue;
                }

                parse_str($body[1], $result);
                if (!is_array($result)) {
                    RY_ECPay_Shipping::log('Shipping failed. Parse result failed.', 'warning');
                    continue;
                }

                $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
                if (!is_array($shipping_list)) {
                    $shipping_list = [];
                }
                if (!isset($shipping_list[$result['AllPayLogisticsID']])) {
                    $shipping_list[$result['AllPayLogisticsID']] = [];
                }
                $shipping_list[$result['AllPayLogisticsID']]['ID'] = $result['AllPayLogisticsID'];
                $shipping_list[$result['AllPayLogisticsID']]['LogisticsType'] = $result['LogisticsType'];
                $shipping_list[$result['AllPayLogisticsID']]['LogisticsSubType'] = $result['LogisticsSubType'];
                $shipping_list[$result['AllPayLogisticsID']]['PaymentNo'] = $result['CVSPaymentNo'];
                $shipping_list[$result['AllPayLogisticsID']]['ValidationNo'] = $result['CVSValidationNo'];
                $shipping_list[$result['AllPayLogisticsID']]['store_ID'] = $args['ReceiverStoreID'];
                $shipping_list[$result['AllPayLogisticsID']]['BookingNote'] = $result['BookingNote'];
                $shipping_list[$result['AllPayLogisticsID']]['status'] = self::get_status($result);
                $shipping_list[$result['AllPayLogisticsID']]['status_msg'] = self::get_status_msg($result);
                $shipping_list[$result['AllPayLogisticsID']]['create'] = $create_datetime->format(DATE_ATOM);
                $shipping_list[$result['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();
                $shipping_list[$result['AllPayLogisticsID']]['amount'] = $args['GoodsAmount'];
                $shipping_list[$result['AllPayLogisticsID']]['IsCollection'] = $args['IsCollection'] ?? 'N';
                if ($method_class::$LogisticsType == 'Home') {
                    $shipping_list[$result['AllPayLogisticsID']]['temp'] = $temp;
                }

                $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                $order->save_meta_data();

                do_action('ry_ecpay_shipping_get_cvs_no', $result, $shipping_list[$result['AllPayLogisticsID']], $order);
            }

            do_action('ry_ecpay_shipping_get_all_cvs_no', $shipping_list, $order);
        }
    }

    public static function get_code_cod($order_id)
    {
        self::get_code($order_id, true);
    }

    public static function get_code_t2($order_id)
    {
        self::get_code($order_id, false, '2');
    }

    public static function get_code_t3($order_id)
    {
        self::get_code($order_id, false, '2');
    }

    public static function get_print_form($info = null)
    {
        list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'LogisticsID' => [],
            'LogisticsSubType' => $info[0]['LogisticsSubType']
        ];

        foreach ($info as $item) {
            if ($item['LogisticsSubType'] == $data['LogisticsSubType']) {
                $data['LogisticsID'][] = $item['ID'];
            }
        }

        $args = self::build_args($data, $MerchantID);
        RY_ECPay_Shipping::log('Print info POST: ' . var_export($args, true));

        if (RY_ECPay_Shipping::$testmode) {
            $post_url = self::$api_test_url['print'];
        } else {
            $post_url = self::$api_url['print'];
        }
        $response = self::link_v2_server($post_url, $args, $HashKey, $HashIV);
        if (is_wp_error($response)) {
            RY_ECPay_Shipping::log('Print failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
            exit();
        }

        if ($response['response']['code'] != '200') {
            RY_ECPay_Shipping::log('Print failed. Http code: ' . $response['response']['code'], 'error');
            return;
        }

        echo $response['body'];
    }
}
