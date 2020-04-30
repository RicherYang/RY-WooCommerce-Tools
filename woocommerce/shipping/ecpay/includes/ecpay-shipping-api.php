<?php
defined('RY_WT_VERSION') or exit('No direct script access allowed');

class RY_ECPay_Shipping_Api extends RY_ECPay
{
    public static $api_test_url = [
        'map' => 'https://logistics-stage.ecpay.com.tw/Express/map',
        'create' => 'https://logistics-stage.ecpay.com.tw/Express/Create',
        'print_UNIMARTC2C' => 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo',
        'print_FAMIC2C' => 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo',
        'print_HILIFEC2C' => 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo',
        'print_B2C' => 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument'
    ];
    public static $api_url = [
        'map' => 'https://logistics.ecpay.com.tw/Express/map',
        'create' => 'https://logistics.ecpay.com.tw/Express/Create',
        'print_UNIMARTC2C' => 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo',
        'print_FAMIC2C' => 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo',
        'print_HILIFEC2C' => 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo',
        'print_B2C' => 'https://logistics.ecpay.com.tw/helper/printTradeDocument'
    ];

    public static function get_map_post_url()
    {
        if (RY_ECPay_Shipping::$testmode) {
            return self::$api_test_url['map'];
        } else {
            return self::$api_url['map'];
        }
    }

    public static function get_code($order_id, $collection = false)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $item_names = [];
        $items = $order->get_items();
        if (count($items)) {
            foreach ($items as $item) {
                $item_names[] = trim($item->get_name());
            }
        }
        $item_names = implode(' ', $item_names);
        $item_names = str_replace(['^','\'','`','!','@','＠','#','%','&','*','+','\\','"','<','>','|','_','[',']'], '', $item_names);
        $item_names = mb_substr($item_names, 0, 25);

        foreach ($order->get_items('shipping') as $item_id => $item) {
            $shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
            if ($shipping_method == false) {
                continue;
            }

            $shipping_list = $order->get_meta('_ecpay_shipping_info', true);
            if (!is_array($shipping_list)) {
                $shipping_list = [];
            }

            $get_count = 1;
            if (count($shipping_list) == 0) {
                $get_count = (int) $item->get_meta('no_count');
            }
            if ($get_count < 1) {
                $get_count = 1;
            }

            $method_class = RY_ECPay_Shipping::$support_methods[$shipping_method];
            list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

            $total = ceil($order->get_total());
            if ($total > 20000) {
                $total = 19999;
            }

            $notify_url = WC()->api_request_url('ry_ecpay_shipping_callback', true);

            RY_ECPay_Shipping::log('Generating shipping for order #' . $order->get_order_number() . ' with ' . $get_count . ' times');

            $args = [
                'MerchantID' => $MerchantID,
                'LogisticsType' => $method_class::$LogisticsType,
                'LogisticsSubType' => $method_class::$LogisticsSubType,
                'GoodsAmount' => (int) $total,
                'GoodsName' => $item_names,
                'SenderName' => RY_WT::get_option('ecpay_shipping_sender_name'),
                'SenderPhone' => RY_WT::get_option('ecpay_shipping_sender_phone'),
                'SenderCellPhone' => RY_WT::get_option('ecpay_shipping_sender_cellphone'),
                'ReceiverName' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
                'ReceiverCellPhone' => $order->get_meta('_shipping_phone'),
                'ReceiverStoreID' => '',
                'ServerReplyURL' => $notify_url,
                'LogisticsC2CReplyURL' => $notify_url,
            ];

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
                $args['Temperature'] = '0001';
                $args['Distance'] = '00';
                $args['Specification'] = '0001';
                $args['ScheduledPickupTime'] = '4';
                $args['ScheduledDeliveryTime'] = '4';
            }

            if (RY_ECPay_Shipping::$testmode) {
                $post_url = self::$api_test_url['create'];
            } else {
                $post_url = self::$api_url['create'];
            }

            for ($i = 0; $i < $get_count; ++$i) {
                $create_datetime = new DateTime('', new DateTimeZone('Asia/Taipei'));
                $args['MerchantTradeDate'] = $create_datetime->format('Y/m/d H:i:s');
                $args['MerchantTradeNo'] = self::generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_shipping_order_prefix'));
                if ($i > 01) {
                    $args['IsCollection'] = 'N';
                    $args['CollectionAmount'] = 0;
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
                    RY_ECPay_Shipping::log('Shipping failed. Explode result failed.', 'error');
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
                    RY_ECPay_Shipping::log('Shipping failed. Parse result failed.', 'error');
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
                $shipping_list[$result['AllPayLogisticsID']]['IsCollection'] = $args['IsCollection'];

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

    public static function get_print_info($logistics_id, $info = null)
    {
        list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

        if (is_array($logistics_id)) {
            $logistics_id = implode(',', $logistics_id);
        }

        $args = [
            'MerchantID' => $MerchantID,
            'AllPayLogisticsID' => $logistics_id,
        ];
        if ($CVS_type == 'C2C') {
            $args['CVSPaymentNo'] = $info['PaymentNo'];
            $args['CVSValidationNo'] = $info['ValidationNo'];
        }
        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
        RY_ECPay_Shipping::log('Print info POST: ' . var_export($args, true));

        if (RY_ECPay_Shipping::$testmode) {
            if ($CVS_type == 'C2C') {
                $post_url = self::$api_test_url['print_' . $info['LogisticsSubType']];
            } else {
                $post_url = self::$api_test_url['print_B2C'];
            }
        } else {
            if ($CVS_type == 'C2C') {
                $post_url = self::$api_url['print_' . $info['LogisticsSubType']];
            } else {
                $post_url = self::$api_url['print_B2C'];
            }
        }

        $response = self::link_server($post_url, $args);
        if (!is_wp_error($response)) {
            if ($response['response']['code'] == '200') {
                return $response['body'];
            } else {
                RY_ECPay_Shipping::log('Print info failed. Http code: ' . $response['response']['code'], 'error');
            }
        } else {
            RY_ECPay_Shipping::log('Print info failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
        }

        return '<!DOCTYPE html><html><head><meta charset="' . get_bloginfo('charset', 'display') . '"></head><body>'
            . __('Error with connect to ECPay server.', 'ry-woocommerce-tools')
            . '</body></html>';
    }

    public static function get_print_info_form($logistics_id, $info = null)
    {
        list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

        if (is_array($logistics_id)) {
            $logistics_id = implode(',', $logistics_id);
        }

        $args = [
            'MerchantID' => $MerchantID,
            'AllPayLogisticsID' => $logistics_id,
        ];
        if (strpos($info['LogisticsSubType'], 'C2C') !== false) {
            $args['CVSPaymentNo'] = $info['PaymentNo'];
            $args['CVSValidationNo'] = $info['ValidationNo'];
        }
        $args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
        RY_ECPay_Shipping::log('Print info POST: ' . var_export($args, true));

        if (RY_ECPay_Shipping::$testmode) {
            if (strpos($info['LogisticsSubType'], 'C2C') !== false) {
                $post_url = self::$api_test_url['print_' . $info['LogisticsSubType']];
            } else {
                $post_url = self::$api_test_url['print_B2C'];
            }
        } else {
            if (strpos($info['LogisticsSubType'], 'C2C') !== false) {
                $post_url = self::$api_url['print_' . $info['LogisticsSubType']];
            } else {
                $post_url = self::$api_url['print_B2C'];
            }
        }

        $html = '<!DOCTYPE html><head><meta charset="' . get_bloginfo('charset', 'display') . '"></head><body>';
        $html .= '<form method="post" id="ry-ecpay-form" action="' . esc_url($post_url) . '" style="display:none;">';
        foreach ($args as $key => $value) {
            $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        $html .= '</form>';
        $html .= '<script>document.getElementById("ry-ecpay-form").submit();</script>';
        $html .= '</body></html>';
        return $html;
    }
}
