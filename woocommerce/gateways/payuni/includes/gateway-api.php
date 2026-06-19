<?php

defined('ABSPATH') or exit;

class RY_WT_WC_PAYUNi_Gateway_Api extends RY_WT_PAYUNi_Api
{
    protected static ?self $_instance = null;

    protected array $api_test_url = [
        'checkout' => 'https://sandbox-api.payuni.com.tw/api/upp',
        'query' => 'https://sandbox-api.payuni.com.tw/api/trade/query',
        'credit-close' => 'https://sandbox-api.payuni.com.tw/api/trade/close',
        'credit-cancel' => 'https://sandbox-api.payuni.com.tw/api/trade/cancel',
        'aftee-refund' => 'https://sandbox-api.payuni.com.tw/api/trade/common/refund/aftee',
        'icash-refund' => 'https://sandbox-api.payuni.com.tw/api/trade/common/refund/icash',
        'jkopay-refund' => 'https://sandbox-api.payuni.com.tw/api/trade/common/refund/jkopay',
        'linepay-refund' => 'https://sandbox-api.payuni.com.tw/api/trade/common/refund/linepay',
    ];

    protected array $api_url = [
        'checkout' => 'https://api.payuni.com.tw/api/upp',
        'query' => 'https://api.payuni.com.tw/api/trade/query',
        'credit-close' => 'https://api.payuni.com.tw/api/trade/close',
        'credit-cancel' => 'https://api.payuni.com.tw/api/trade/cancel',
        'aftee-refund' => 'https://api.payuni.com.tw/api/trade/common/refund/aftee',
        'icash-refund' => 'https://api.payuni.com.tw/api/trade/common/refund/icash',
        'jkopay-refund' => 'https://api.payuni.com.tw/api/trade/common/refund/jkopay',
        'linepay-refund' => 'https://api.payuni.com.tw/api/trade/common/refund/linepay',
    ];

    public static function instance(): RY_WT_WC_PAYUNi_Gateway_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkout_form($order, $gateway)
    {
        $notify_url = WC()->api_request_url('ry_payuni_callback', true);
        $return_url = $this->get_3rd_return_url($order);

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name($api_info['itemname'], $order);
        $item_name = mb_substr($item_name, 0, 195);

        $data = [
            'MerID' => $api_info['MerID'],
            'MerTradeNo' => $this->generate_trade_no($order->get_id(), $api_info['prefix']),
            'TradeAmt' => (int) ceil($order->get_total()),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'ReturnURL' => $return_url,
            'NotifyURL' => $notify_url,
            'BackURL' => $return_url,
            'UsrMail' => $order->get_billing_email(),
            'UsrMailFix' => 1,
            'ProdDesc' => $item_name,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();
        switch (get_locale()) {
            case 'zh_HK':
            case 'zh_TW':
                break;
            case 'en_US':
            case 'en_AU':
            case 'en_CA':
            case 'en_GB':
            default:
                $data['Lang'] = 'en';
                break;
        }

        $data = $this->add_type_info($data, $order, $gateway);
        $args = $this->build_args($data, '2.0');
        RY_WT_WC_PAYUNi_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $data]);

        $order->update_meta_data('_payuni_MerTradeNo', $data['MerTradeNo']);
        $order->save();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        $this->auto_submit_data($url, $args);

        do_action('ry_payuni_gateway_checkout', $data, $order, $gateway);
    }

    public function get_info($order)
    {
        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'MerTradeNo' => $order->get_meta('_payuni_MerTradeNo', true),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }

        $args = $this->build_args($data, '2.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $args, 'Query');
    }

    public function credit_close($order, $action, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'CloseType' => $action === 'C' ? '1' : '2',
            'TradeAmt' => $amount,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['credit-close'];
        } else {
            $url = $this->api_url['credit-close'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'CreditClose');
    }

    public function credit_cancel($order)
    {
        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['credit-cancel'];
        } else {
            $url = $this->api_url['credit-cancel'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'CreditCancel');
    }

    public function aftee_refund($order, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['aftee-refund'];
        } else {
            $url = $this->api_url['aftee-refund'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'AfteeRefund');
    }

    public function icash_refund($order, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['icash-refund'];
        } else {
            $url = $this->api_url['icash-refund'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'IcashRefund');
    }

    public function jkopay_refund($order, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['jkopay-refund'];
        } else {
            $url = $this->api_url['jkopay-refund'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'JkopayRefund');
    }

    public function linepay_refund($order, $amount)
    {
        $amount = (int) $amount;

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $data = [
            'MerID' => $api_info['MerID'],
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $data['Timestamp'] = $data['Timestamp']->getTimestamp();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['linepay-refund'];
        } else {
            $url = $this->api_url['linepay-refund'];
        }

        $args = $this->build_args($data, '1.0');
        $response = $this->link_server($url, $args);
        return $this->get_decrypt_result($response, $data, 'LinepayRefund');
    }

    protected function add_type_info($data, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::PAYMENT_TYPE')) {
            $data[$gateway::PAYMENT_TYPE] = 1;
            switch ($gateway::PAYMENT_TYPE) {
                case 'VACC':
                case 'CVS':
                    $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
                    $now->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $data['ExpireDate'] = $now->format('Ymd');
                    break;
                case 'CreditInst':
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        $data[$gateway::PAYMENT_TYPE] = implode(',', $gateway->number_of_periods);
                    }
                    break;
                case 'Digital':
                    unset($data[$gateway::PAYMENT_TYPE]);
                    $data['ICash'] = 1;
                    $data['JKoPay'] = 1;
                    $data['LinePay'] = 1;
                    break;
            }
        }

        return $data;
    }

    protected function get_decrypt_result($response, $data, $log_prefix = '')
    {
        if (is_wp_error($response)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' failed', WC_Log_Levels::ERROR, ['data' => $data, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' HTTP status error', WC_Log_Levels::ERROR, ['data' => $data, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $ipn_info = json_decode($body, true);
        if (!is_array($ipn_info)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' result parse failed', WC_Log_Levels::WARNING, ['data' => $data, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        $api_info = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $check_value = $this->get_hash_value($ipn_info);
        if (empty($check_value)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' result unknown check value', WC_Log_Levels::WARNING, ['data' => $ipn_info]);
            return;
        }

        $info_value = $this->get_info_value($ipn_info);
        $ipn_info_check_value = $this->generate_hash_value($info_value, $api_info['HashKey'], $api_info['HashIV']);
        if ($check_value !== $ipn_info_check_value) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' result check failed', WC_Log_Levels::WARNING, ['data' => $ipn_info, 'self' => $ipn_info_check_value]);
            return;
        }

        $info_value = $this->data_decrypt($info_value, $api_info['HashKey'], $api_info['HashIV']);
        parse_str($info_value, $info_value);

        return $info_value;
    }
}
