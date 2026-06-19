<?php

defined('ABSPATH') or exit;

class RY_WT_WC_NewebPay_Gateway_Api extends RY_WT_NewebPay_Api
{
    protected static ?self $_instance = null;

    protected array $api_test_url = [
        'checkout' => 'https://ccore.newebpay.com/MPG/mpg_gateway',
        'query' => 'https://ccore.newebpay.com/API/QueryTradeInfo',
    ];

    protected array $api_url = [
        'checkout' => 'https://core.newebpay.com/MPG/mpg_gateway',
        'query' => 'https://core.newebpay.com/API/QueryTradeInfo',
    ];

    public static function instance(): RY_WT_WC_NewebPay_Gateway_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkout_form($order, $gateway)
    {
        $notify_url = WC()->api_request_url('ry_newebpay_callback', true);
        $return_url = $this->get_3rd_return_url($order);

        $api_info = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name($api_info['itemname'], $order);
        $item_name = mb_substr($item_name, 0, 40);

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'RespondType' => 'JSON',
            'TimeStamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'Version' => '2.3',
            'MerchantOrderNo' => $this->generate_trade_no($order->get_id(), $api_info['prefix']),
            'Amt' => (int) ceil($order->get_total()),
            'ItemDesc' => $item_name,
            'ReturnURL' => $return_url,
            'NotifyURL' => $notify_url,
            'CustomerURL' => $return_url,
            'Email' => $order->get_billing_email(),
            'EmailModify' => 0,
            'CREDIT' => 0,
            'APPLEPAY' => 0,
            'ANDROIDPAY' => 0,
            'SAMSUNGPAY' => 0,
            'LINEPAY' => 0,
            'AFTEE' => 0,
            'InstFlag' => 0,
            'CreditRed' => 0,
            'UNIONPAY' => 0,
            'CREDITAE' => 0,
            'WEBATM' => 0,
            'VACC' => 0,
            'CVS' => 0,
            'BARCODE' => 0,
            'ESUNWALLET' => 0,
            'TAIWANPAY' => 0,
            'BITOPAY' => 0,
            'TWQR' => 0,
            'EZPWECHAT' => 0,
            'EZPALIPAY' => 0,
            'CVSCOM' => 0,
        ];
        $args['TimeStamp'] = $args['TimeStamp']->getTimestamp();
        switch (get_locale()) {
            case 'zh_HK':
            case 'zh_TW':
                break;
            case 'ja':
                $args['LangType'] = 'jp';
                break;
            case 'en_US':
            case 'en_AU':
            case 'en_CA':
            case 'en_GB':
            default:
                $args['LangType'] = 'en';
                break;
        }

        $args = $this->add_type_info($args, $order, $gateway);
        $form_data = [
            'MerchantID' => $api_info['MerchantID'],
            'TradeInfo' => $this->args_encrypt($args, $api_info['HashKey'], $api_info['HashIV']),
            'Version' => '2.3',
            'EncryptType' => 0,
        ];
        $form_data['TradeSha'] = $this->generate_hash_value($form_data['TradeInfo'], $api_info['HashKey'], $api_info['HashIV']);
        RY_WT_WC_NewebPay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        $order->update_meta_data('_newebpay_MerchantOrderNo', $args['MerchantOrderNo']);
        $order->save();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        $this->auto_submit_data($url, $form_data);

        do_action('ry_newebpay_gateway_checkout', $args, $order, $gateway);
    }

    public function get_info($order)
    {
        $api_info = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();

        $args = [
            'MerchantID' => $api_info['MerchantID'],
            'Version' => '1.3',
            'RespondType' => 'JSON',
            'TimeStamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'MerchantOrderNo' => $order->get_meta('_newebpay_MerchantOrderNo', true),
            'Amt' => (int) ceil($order->get_total()),
        ];
        $args['TimeStamp'] = $args['TimeStamp']->getTimestamp();
        $args['CheckValue'] = $this->generate_hash_value($args, $api_info['HashKey'], $api_info['HashIV']);

        if ($api_info['testmode']) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_NewebPay_Gateway::instance()->log('Query failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_NewebPay_Gateway::instance()->log('Query HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if (!is_array($result)) {
            RY_WT_WC_NewebPay_Gateway::instance()->log('Query result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        return $result;
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::PAYMENT_TYPE')) {
            if (isset($args[$gateway::PAYMENT_TYPE])) {
                $args[$gateway::PAYMENT_TYPE] = 1;
            }
            switch ($gateway::PAYMENT_TYPE) {
                case 'VACC':
                case 'CVS':
                case 'BARCODE':
                    $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
                    $now->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $args['ExpireDate'] = $now->format('Ymd');
                    break;
                case 'InstFlag':
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        $args['InstFlag'] = implode(',', $gateway->number_of_periods);
                    }
                    break;
            }
        }

        if (class_exists('RY_WT_WC_NewebPay_Shipping')) {
            foreach ($order->get_items('shipping') as $shipping_item) {
                $shipping_method = RY_WT_WC_NewebPay_Shipping::instance()->get_order_support_shipping($shipping_item);
                if ($shipping_method) {
                    if ('cod' === $gateway->id) {
                        $args['CVSCOM'] = 2;
                    } else {
                        $args['CVSCOM'] = 1;
                    }
                    break;
                }
            }
        }

        return $args;
    }
}
