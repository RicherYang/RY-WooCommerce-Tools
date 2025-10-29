<?php

class RY_WT_WC_ECPay_Gateway_Api extends RY_WT_ECPay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'credit-query' => 'https://ecpayment-stage.ecpay.com.tw/1.0.0/CreditDetail/QueryTrade',
        'credit-action' => 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction',
    ];

    protected $api_url = [
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

        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('payment_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 195);

        $args = [
            'MerchantID' => $MerchantID,
            'MerchantTradeNo' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_gateway_order_prefix')),
            'MerchantTradeDate' => new DateTime('', new DateTimeZone('Asia/Taipei')),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) ceil($order->get_total()),
            'TradeDesc' => get_bloginfo('name'),
            'ItemName' => $item_name,
            'ReturnURL' => $notify_url,
            'ChoosePayment' => $gateway::Payment_Type,
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
            case 'zh_CN':
                $args['Language'] = 'CHI';
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
        $args = $this->add_check_value($args, $HashKey, $HashIV, 'sha256');
        RY_WT_WC_ECPay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        $order->update_meta_data('_ecpay_MerchantTradeNo', $args['MerchantTradeNo']);
        $order->save();

        if (RY_WT_WC_ECPay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }
        echo '<form method="post" id="ry-ecpay-form" action="' . esc_url($url) . '">';
        foreach ($args as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';
        $this->submit_sctipt('document.getElementById("ry-ecpay-form").submit();');

        do_action('ry_ecpay_gateway_checkout', $args, $order, $gateway);
    }

    public function get_info($order)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
        ];

        $args['TimeStamp'] = $args['TimeStamp']->getTimestamp();

        if (RY_WT_WC_ECPay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }
        $args = $this->add_check_value($args, $HashKey, $HashIV, 'sha256');

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

        $check_value = $this->generate_check_value($result, $HashKey, $HashIV, 'sha256');
        if ($check_value !== $result['CheckMacValue']) {
            RY_WT_WC_ECPay_Gateway::instance()->log('Query request check failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => $result['CheckMacValue'], 'check_value' => $check_value]);
            return;
        }

        return $result;
    }

    public function get_credit_info($order)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'RqHeader' => [
                'Timestamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
            ],
            'Data' => wp_json_encode([
                'MerchantID' => $MerchantID,
                'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            ]),
        ];
        $args['RqHeader']['Timestamp'] = $args['RqHeader']['Timestamp']->getTimestamp();

        if (RY_WT_WC_ECPay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['credit-query'];
        } else {
            $url = $this->api_url['credit-query'];
        }

        $response = $this->link_v2_server($url, $args, $HashKey, $HashIV);
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

        $result->Data = openssl_decrypt($result->Data, self::Encrypt_Method, $HashKey, 0, $HashIV);
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

        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_ECPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo', true),
            'TradeNo' => $order->get_transaction_id(),
            'Action' => $action,
            'TotalAmount' => $amount,
        ];

        if (RY_WT_WC_ECPay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['credit-action'];
        } else {
            $url = $this->api_url['credit-action'];
        }
        $args = $this->add_check_value($args, $HashKey, $HashIV, 'sha256');

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
        switch ($gateway::Payment_Type) {
            case 'Credit':
                if (isset($gateway->support_applepay) && $gateway->support_applepay === 'no') {
                    $args['IgnorePayment'] = 'ApplePay';
                }
                if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                    if (is_array($gateway->number_of_periods)) {
                        $number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
                        if (!in_array($number_of_periods, $gateway->number_of_periods)) {
                            $number_of_periods = 0;
                        }
                    } else {
                        $number_of_periods = (int) $gateway->number_of_periods;
                    }
                    if (in_array($number_of_periods, [3, 6, 12, 18, 24])) {
                        $args['CreditInstallment'] = $number_of_periods;
                        $order->add_order_note(sprintf(
                            /* translators: %d number of periods */
                            __('Credit installment to %d', 'ry-woocommerce-tools'),
                            $number_of_periods,
                        ));
                        $order->save();
                    }
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
        return $args;
    }
}
