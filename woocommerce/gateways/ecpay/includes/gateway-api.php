<?php

defined('ABSPATH') or exit;

final class RY_WT_WC_ECPay_Gateway_Api extends RY_WT_ECPay_Api
{
    private static ?self $_instance = null;

    protected array $api_test_url = [
        'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'credit-query' => 'https://ecpayment-stage.ecpay.com.tw/1.0.0/CreditDetail/QueryTrade',
        'credit-action' => 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction',
    ];

    protected array $api_url = [
        'checkout' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'credit-query' => 'https://ecpayment.ecpay.com.tw/1.0.0/CreditDetail/QueryTrade',
        'credit-action' => 'https://payment.ecpay.com.tw/CreditDetail/DoAction',
    ];

    public static function instance(): RY_WT_WC_ECPay_Gateway_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkout_form($order, $gateway)
    {
        $notify_url = WC()->api_request_url('ry_ecpay_callback', true);
        $return_url = $this->get_3rd_return_url($order);

        $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name($api_info['itemname'], $order);
        $item_name = mb_substr($item_name, 0, 195);

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'MerchantTradeNo' => $this->generate_trade_no($order->get_id(), $api_info['prefix']),
            'MerchantTradeDate' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) ceil($order->get_total()),
            'TradeDesc' => get_bloginfo('name'),
            'ItemName' => $item_name,
            'ReturnURL' => $notify_url,
            'ClientBackURL' => $return_url,
            'OrderResultURL' => $return_url,
            'NeedExtraPaidInfo' => 'Y',
            'IgnorePayment' => '',
            'EncryptType' => 1,
            'PaymentInfoURL' => $notify_url,
            'ClientRedirectURL' => $return_url,
        ];
        $args['TradeDesc'] = preg_replace('/[\x{21}-\x{2f}\x{3a}-\x{40}\x{5b}-\x{60}\x{7b}-\x{7e}]/', ' ', $args['TradeDesc']);
        $args['TradeDesc'] = mb_substr($args['TradeDesc'], 0, 100);
        $args['MerchantTradeDate'] = $args['MerchantTradeDate']->format('Y/m/d H:i:s');

        switch (get_locale()) {
            case 'zh_HK':
            case 'zh_TW':
                break;
            case 'ko_KR':
                $args['Language'] = 'KOR';
                break;
            case 'ja':
                $args['Language'] = 'JPN';
                break;
            case 'en_US':
            case 'en_AU':
            case 'en_CA':
            case 'en_GB':
            default:
                $args['Language'] = 'ENG';
                break;
        }

        $args = $this->add_type_info($args, $order, $gateway);
        $args['CheckMacValue'] = $this->generate_hash_value($args, $api_info['HashKey'], $api_info['HashIV'], 'sha256');
        RY_WT_WC_ECPay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        $order->update_meta_data('_ecpay_MerchantTradeNo', $args['MerchantTradeNo']);
        $order->save();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        $this->auto_submit_data($url, $args);

        do_action('ry_ecpay_gateway_checkout', $args, $order, $gateway);
    }

    public function get_info($order)
    {
        $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            'TimeStamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
        ];

        $args['TimeStamp'] = $args['TimeStamp']->getTimestamp();
        $args['CheckMacValue'] = $this->generate_hash_value($args, $api_info['HashKey'], $api_info['HashIV'], 'sha256');

        if ($api_info['testmode']) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Query failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_ECPay_Gateway::instance()->log('Query HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        parse_str($body, $result);
        if (!is_array($result)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Query result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        $check_value = $this->generate_hash_value($result, $api_info['HashKey'], $api_info['HashIV'], 'sha256');
        if ($check_value !== $result['CheckMacValue']) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Query request check failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => $result['CheckMacValue'], 'check_value' => $check_value]);
            return;
        }

        return $result;
    }

    public function get_credit_info($order)
    {
        $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'RqHeader' => [
                'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            ],
            'Data' => wp_json_encode([
                'MerchantID' => $api_info['MerchantID'],
                'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            ]),
        ];
        $args['RqHeader']['Timestamp'] = $args['RqHeader']['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['credit-query'];
        } else {
            $url = $this->api_url['credit-query'];
        }

        $response = $this->link_v2_server($url, $args, $api_info['HashKey'], $api_info['HashIV']);
        if (is_wp_error($response)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit query failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit query HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);
        if (!is_object($result)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit query result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        if (!(isset($result->TransCode) && 1 == $result->TransCode)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit query result error', WC_Log_Levels::ERROR, ['code' => $result->TransCode, 'msg' => $result->TransMsg]);
            return;
        }

        $result->Data = openssl_decrypt($result->Data, self::ENCRYPT_METHOD, $api_info['HashKey'], 0, $api_info['HashIV']);
        $result->Data = json_decode(urldecode($result->Data), true);

        if (!is_array($result->Data)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit query data decrypt failed', WC_Log_Levels::ERROR, ['data' => $result->Data]);
            return;
        }

        RY_WT_WC_ECPay_Gateway::instance()->log('Credit query data', WC_Log_Levels::INFO, ['data' => $result->Data]);

        return $result->Data;
    }

    public function credit_action($order, $action, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            'TradeNo' => $order->get_transaction_id(),
            'Action' => $action,
            'TotalAmount' => $amount,
        ];
        $args['CheckMacValue'] = $this->generate_hash_value($args, $api_info['HashKey'], $api_info['HashIV'], 'sha256');

        if ($api_info['testmode']) {
            $url = $this->api_test_url['credit-action'];
        } else {
            $url = $this->api_url['credit-action'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit action failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit action HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        parse_str($body, $result);
        if (!is_array($result)) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Credit action result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        RY_WT_WC_ECPay_Gateway::instance()->log('Credit action data', WC_Log_Levels::INFO, ['data' => $result]);

        return $result;
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::PAYMENT_TYPE')) {
            $args['ChoosePayment'] = $gateway::PAYMENT_TYPE;

            if (defined(get_class($gateway) . '::SUB_PAYMENT_TYPE')) {
                $args['ChooseSubPayment'] = $gateway::SUB_PAYMENT_TYPE;
            }

            $args['IgnorePayment'] = ['WeiXin'];
            switch ($gateway::PAYMENT_TYPE) {
                case 'Credit':
                    if (isset($gateway->support_union) && $gateway->support_union === true) {
                        $args['UnionPay'] = 0;
                    } else {
                        $args['UnionPay'] = 2;
                    }
                    $args['IgnorePayment'][] = 'DigitalPayment';
                    if (isset($gateway->support_applepay) && $gateway->support_applepay === false) {
                        $args['IgnorePayment'][] = 'ApplePay';
                    }
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        $args['CreditInstallment'] = implode(',', $gateway->number_of_periods);
                    }
                    break;
                case 'ATM':
                    $args['ExpireDate'] = $gateway->expire_date;
                    break;
                case 'BARCODE':
                case 'CVS':
                    $args['StoreExpireDate'] = $gateway->expire_date;
                    break;
            }
            $args['IgnorePayment'] = implode('#', $args['IgnorePayment']);
        }

        return $args;
    }
}
