<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

class RY_WT_WC_ECPay_Shipping_Api extends RY_WT_ECPay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'map' => 'https://logistics-stage.ecpay.com.tw/Express/map',
        'create' => 'https://logistics-stage.ecpay.com.tw/Express/Create',
        'print' => 'https://logistics-stage.ecpay.com.tw/Express/v2/PrintTradeDocument',
    ];

    protected $api_url = [
        'map' => 'https://logistics.ecpay.com.tw/Express/map',
        'create' => 'https://logistics.ecpay.com.tw/Express/Create',
        'print' => 'https://logistics.ecpay.com.tw/Express/v2/PrintTradeDocument',
    ];

    public static function instance(): RY_WT_WC_ECPay_Shipping_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function get_map_post_url()
    {
        if (RY_WT_WC_ECPay_Shipping::instance()->is_testmode()) {
            return $this->api_test_url['map'];
        } else {
            return $this->api_url['map'];
        }
    }

    public function get_code($order_ID, $collection = false, $for_temp = null)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        $item_name = $this->get_item_name(RY_WT::get_option('shipping_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 20);
        $declare_over_type = RY_WT::get_option('ecpay_shipping_declare_over', 'keep');

        foreach ($order->get_items('shipping') as $shipping_item) {
            $shipping_method = RY_WT_WC_ECPay_Shipping::instance()->get_order_support_shipping($shipping_item);
            if ($shipping_method == false) {
                continue;
            }

            $method_class = RY_WT_WC_ECPay_Shipping::$support_methods[$shipping_method];
            list($MerchantID, $HashKey, $HashIV, $cvs_type) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();

            $package_list = [];
            $temp_package = [];
            $basic_package = [
                'price' => 0,
                'fee' => 0,
                'weight' => 0,
                'size' => 0,
                'items' => 0,
            ];
            foreach ($order->get_items('line_item') as $item) {
                $product = $item->get_product();

                if ($product) {
                    $temp = $product->get_meta('_ry_shipping_temp', true);
                    if (empty($temp) && 'variation' === $product->get_type()) {
                        $parent_product = wc_get_product($product->get_parent_id());
                        $temp = $parent_product->get_meta('_ry_shipping_temp', true);
                    }
                    $temp = in_array($temp, $method_class::get_support_temp()) ? $temp : '1';
                    $weight = $product->get_weight();
                    $size = (float) $product->get_length() + (float) $product->get_width() + (float) $product->get_height();

                    $shipping_amount = $product->get_meta('_ry_shipping_amount', true);
                    if ('' == $shipping_amount) {
                        if ('variation' === $product->get_type()) {
                            $parent_product = wc_get_product($product->get_parent_id());
                            $shipping_amount = $parent_product->get_meta('_ry_shipping_amount', true);
                        }
                    }
                    $shipping_amount = NumberUtil::round($shipping_amount, wc_get_price_decimals());
                    if (0 >= $shipping_amount) {
                        $shipping_amount = $product->get_regular_price();
                    }
                    $shipping_amount = NumberUtil::round($shipping_amount, wc_get_price_decimals());
                    $item_price = $shipping_amount * $item->get_quantity();
                } else {
                    $temp = 1;
                    $weight = '';
                    $size = 0;
                    $item_price = $item->get_subtotal();
                }

                if (null !== $for_temp && $temp != $for_temp) {
                    continue;
                }

                if (!isset($temp_package[$temp])) {
                    $package_list[] = $basic_package;
                    $temp_package[$temp] = array_key_last($package_list);
                    $package_list[$temp_package[$temp]]['temp'] = $temp;
                }

                if ('' == $weight) {
                    $weight = RY_WT::get_option('ecpay_shipping_product_weight', 0);
                }
                $weight = (float) $weight;

                if ('multi' === $declare_over_type) {
                    if (20000 < $item_price) {
                        array_unshift($package_list, $basic_package);
                        $package_list[0]['temp'] = $temp;
                        $package_list[0]['items'] += 1;
                        $package_list[0]['price'] += $item_price;
                        $package_list[0]['fee'] += $item->get_total();
                        $package_list[0]['weight'] += $weight * $item->get_quantity();
                        $package_list[0]['size'] = $size;

                        $temp_package[$temp] += 1;
                        continue;
                    }

                    if (20000 < $package_list[$temp_package[$temp]]['price'] + $item_price) {
                        $package_list[] = $basic_package;
                        $temp_package[$temp] = array_key_last($package_list);
                        $package_list[$temp_package[$temp]]['temp'] = $temp;
                    }
                }

                $package_list[$temp_package[$temp]]['items'] += 1;
                $package_list[$temp_package[$temp]]['price'] += $item_price;
                $package_list[$temp_package[$temp]]['fee'] += $item->get_total();
                $package_list[$temp_package[$temp]]['weight'] += $weight * $item->get_quantity();
                $package_list[$temp_package[$temp]]['size'] = max($size, $package_list[$temp_package[$temp]]['size']);
            }

            foreach ($package_list as $idx => $package_info) {
                if (0 === $package_info['items']) {
                    unset($package_list[$idx]);
                    continue;
                }

                $package_info['price'] = (int) $package_info['price'];
                $package_info['fee'] = (int) $package_info['fee'];
            }

            if (0 === count($package_list)) {
                continue;
            }

            usort($package_list, function ($a, $b) {
                return $a['temp'] <=> $b['temp'];
            });

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }

            $notify_url = WC()->api_request_url('ry_ecpay_shipping_callback', true);

            RY_WT_WC_ECPay_Shipping::instance()->log('Generating shipping for #' . $order->get_id(), WC_Log_Levels::INFO);

            $args = [
                'MerchantID' => $MerchantID,
                'LogisticsType' => $method_class::Shipping_Type,
                'GoodsName' => $item_name,
                'IsCollection' => 'N',
                'CollectionAmount' => 0,
                'SenderName' => RY_WT::get_option('ecpay_shipping_sender_name'),
                'SenderPhone' => RY_WT::get_option('ecpay_shipping_sender_phone'),
                'SenderCellPhone' => RY_WT::get_option('ecpay_shipping_sender_cellphone'),
                'ReceiverName' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                'ReceiverCellPhone' => str_replace(['-', ' '], ' ', $order->get_shipping_phone()),
                'ReceiverStoreID' => '',
                'ServerReplyURL' => $notify_url,
            ];

            if ('yes' === RY_WT::get_option('ecpay_shipping_cleanup_receiver_name', 'no')) {
                $args['ReceiverName'] = preg_replace('/[^a-zA-Z\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', '', $args['ReceiverName']);
                if (preg_match('/^[a-zA-z]+$/', $args['ReceiverName'])) {
                    $args['ReceiverName'] = mb_substr($args['ReceiverName'], 0, 10);
                } else {
                    $args['ReceiverName'] = preg_replace('/[a-zA-Z]/', '', $args['ReceiverName']);
                    $args['ReceiverName'] = mb_substr($args['ReceiverName'], 0, 5);
                }
            }

            if (0 === count($shipping_list)) {
                if ('cod' === $order->get_payment_method()) {
                    $args['IsCollection'] = 'Y';
                }
            }
            if (true === $collection) {
                $args['IsCollection'] = 'Y';
            }
            if ('Y' === $args['IsCollection']) {
                $total_amount = 0;
                foreach ($package_list as $package_info) {
                    $total_amount += $package_info['fee'];
                }
                if ($order->get_total() != $total_amount) {
                    $package_list[0]['fee'] += $order->get_total() - $total_amount;
                }
            }

            if ('CVS' === $args['LogisticsType']) {
                $args['ReceiverStoreID'] = $order->get_meta('_shipping_cvs_store_ID');
            }

            if ('Home' === $args['LogisticsType']) {
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

            if (RY_WT_WC_ECPay_Shipping::instance()->is_testmode()) {
                $post_url = $this->api_test_url['create'];
            } else {
                $post_url = $this->api_url['create'];
            }

            foreach ($package_list as $package_info) {
                $create_datetime = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $args['MerchantTradeDate'] = $create_datetime->format('Y/m/d H:i:s');
                $args['MerchantTradeNo'] = $this->generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_shipping_order_prefix')) . 'T' . $package_info['temp'];

                if ('CVS' === $args['LogisticsType']) {
                    $args['LogisticsSubType'] = $method_class::Shipping_Sub_Type;
                    if ('C2C' === $cvs_type) {
                        $args['LogisticsSubType'] .= 'C2C';
                    }
                    if ('UNIMART' === $args['LogisticsSubType']) {
                        if ('3' == $package_info['temp']) {
                            $args['LogisticsSubType'] .= 'FREEZE';
                        }
                    }
                }
                if ('Home' === $args['LogisticsType']) {
                    $args['LogisticsSubType'] = $method_class::Shipping_Sub_Type;
                    if ($package_info['weight'] > 0) {
                        $args['GoodsWeight'] = round(wc_get_weight($package_info['weight'], 'kg'), 3);
                    }
                    if ($args['Specification'] == '0000') {
                        if ($package_info['size'] > 0) {
                            $package_info['size'] = wc_get_dimension($package_info['size'], 'cm');
                            if ($package_info['size'] <= 60) {
                                $args['Specification'] = '0001';
                            } elseif ($package_info['size'] <= 90) {
                                $args['Specification'] = '0002';
                            } elseif ($package_info['size'] <= 120) {
                                $args['Specification'] = '0003';
                            } else {
                                $args['Specification'] = '0004';
                            }
                        } else {
                            $args['Specification'] = '0001';
                        }
                    }
                    $args['Temperature'] = '000' . $package_info['temp'];
                }

                if ('limit' === $declare_over_type) {
                    if (20000 < $package_info['price']) {
                        $package_info['price'] = 20000;
                    }
                }
                $args['GoodsAmount'] = (int) $package_info['price'];
                if ('Y' === $args['IsCollection']) {
                    $args['CollectionAmount'] = (int) $package_info['fee'];
                    if ('UNIMARTC2C' === $args['LogisticsSubType']) {
                        $args['GoodsAmount'] = $args['CollectionAmount'];
                    }
                }

                $args = $this->add_check_value($args, $HashKey, $HashIV, 'md5');
                RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST data', WC_Log_Levels::INFO, ['data' => $args]);

                $response = $this->link_server($post_url, $args);
                if (is_wp_error($response)) {
                    RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
                    continue;
                }

                if (wp_remote_retrieve_response_code($response) != '200') {
                    RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST HTTP status error', WC_Log_Levels::ERROR, ['code' => wp_remote_retrieve_response_code($response)]);
                    continue;
                }

                $body = explode('|', wp_remote_retrieve_body($response));
                if (count($body) != 2) {
                    RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST result explode failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
                    continue;
                }

                if ($body[0] != '1') {
                    $order->add_order_note(sprintf(
                        /* translators: %s Error messade */
                        __('Get shipping code error: %s', 'ry-woocommerce-tools'),
                        $body[1],
                    ));
                    continue;
                }

                parse_str($body[1], $result);
                if (!is_array($result)) {
                    RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST result parse failed', WC_Log_Levels::WARNING, ['data' => wp_remote_retrieve_body($response)]);
                    continue;
                }

                RY_WT_WC_ECPay_Shipping::instance()->log('Shipping POST result', WC_Log_Levels::INFO, ['status' => $body[0], 'data' => $result]);

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
                $shipping_list[$result['AllPayLogisticsID']]['status'] = $this->get_status($result);
                $shipping_list[$result['AllPayLogisticsID']]['status_msg'] = $this->get_status_msg($result);
                $shipping_list[$result['AllPayLogisticsID']]['create'] = $create_datetime->format(DATE_ATOM);
                $shipping_list[$result['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();
                $shipping_list[$result['AllPayLogisticsID']]['amount'] = $args['GoodsAmount'];
                $shipping_list[$result['AllPayLogisticsID']]['IsCollection'] = $args['IsCollection'] === 'Y' ? $args['CollectionAmount'] : 'N';
                $shipping_list[$result['AllPayLogisticsID']]['temp'] = $package_info['temp'];

                $order->update_meta_data('_ecpay_shipping_info', $shipping_list);
                $order->save();

                do_action('ry_ecpay_shipping_get_cvs_no', $result, $shipping_list[$result['AllPayLogisticsID']], $order);
            }

            do_action('ry_ecpay_shipping_get_all_cvs_no', $shipping_list, $order);
        }
    }

    public function get_print_form($info = null)
    {
        list($MerchantID, $HashKey, $HashIV, $cvs_type) = RY_WT_WC_ECPay_Shipping::instance()->get_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'LogisticsID' => [],
            'LogisticsSubType' => $info[0]['LogisticsSubType'],
        ];

        foreach ($info as $item) {
            if ($item['LogisticsSubType'] == $data['LogisticsSubType']) {
                $data['LogisticsID'][] = $item['ID'];
            }
        }

        $args = $this->build_args($data, $MerchantID);
        RY_WT_WC_ECPay_Shipping::instance()->log('Print POST data', WC_Log_Levels::INFO, ['data' => $args]);

        if (RY_WT_WC_ECPay_Shipping::instance()->is_testmode()) {
            $post_url = $this->api_test_url['print'];
        } else {
            $post_url = $this->api_url['print'];
        }
        $response = $this->link_v2_server($post_url, $args, $HashKey, $HashIV);
        if (is_wp_error($response)) {
            RY_WT_WC_ECPay_Shipping::instance()->log('Print POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            exit();
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_ECPay_Shipping::instance()->log('Print POST HTTP status error', WC_Log_Levels::ERROR, ['code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        echo wp_remote_retrieve_body($response);
    }
}
