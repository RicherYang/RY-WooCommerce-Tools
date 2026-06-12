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

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('payment_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 195);

        $args = [
            'MerID' => $MerID,
            'MerTradeNo' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('payuni_gateway_order_prefix')),
            'TradeAmt' => (int) ceil($order->get_total()),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'ReturnURL' => $return_url,
            'NotifyURL' => $notify_url,
            'BackURL' => $return_url,
            'UsrMail' => $order->get_billing_email(),
            'UsrMailFix' => 1,
            'ProdDesc' => $item_name,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();
        switch (get_locale()) {
            case 'zh_HK':
            case 'zh_TW':
                break;
            case 'en_US':
            case 'en_AU':
            case 'en_CA':
            case 'en_GB':
            default:
                $args['Lang'] = 'en';
                break;
        }

        $args = $this->add_type_info($args, $order, $gateway);
        $form_data = [
            'MerID' => $MerID,
            'EncryptInfo' => $this->args_encrypt($args, $HashKey, $HashIV),
            'Version' => '2.0',
        ];
        $form_data['HashInfo'] = $this->generate_hash_value($form_data['EncryptInfo'], $HashKey, $HashIV);
        RY_WT_WC_PAYUNi_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['form' => $form_data, 'data' => $args]);

        $order->update_meta_data('_payuni_MerTradeNo', $args['MerTradeNo']);
        $order->save();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        echo '<form method="post" id="ry-payuni-form" action="' . esc_url($url) . '">';
        foreach ($form_data as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';
        $this->submit_sctipt('document.getElementById("ry-payuni-form").submit();');

        do_action('ry_payuni_gateway_checkout', $args, $order, $gateway);
    }

    public function get_info($order)
    {
        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'MerTradeNo' => $order->get_meta('_payuni_MerTradeNo', true),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }

        $response = $this->link_server($url, $args, '2.0');
        return $this->get_decrypt_result($response, $args, 'Query');
    }

    public function credit_close($order, $action, $amount)
    {
        $amount = (int) $amount;

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'CloseType' => $action === 'C' ? '1' : '2',
            'TradeAmt' => $amount,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['credit-close'];
        } else {
            $url = $this->api_url['credit-close'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'CreditClose');
    }

    public function credit_cancel($order)
    {
        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['credit-cancel'];
        } else {
            $url = $this->api_url['credit-cancel'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'CreditCancel');
    }

    public function aftee_refund($order, $amount)
    {
        $amount = (int) $amount;

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['aftee-refund'];
        } else {
            $url = $this->api_url['aftee-refund'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'AfteeRefund');
    }

    public function icash_refund($order, $amount)
    {
        $amount = (int) $amount;

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['icash-refund'];
        } else {
            $url = $this->api_url['icash-refund'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'IcashRefund');
    }

    public function jkopay_refund($order, $amount)
    {
        $amount = (int) $amount;

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['jkopay-refund'];
        } else {
            $url = $this->api_url['jkopay-refund'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'JkopayRefund');
    }

    public function linepay_refund($order, $amount)
    {
        $amount = (int) $amount;

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $args = [
            'MerID' => $MerID,
            'TradeNo' => $order->get_transaction_id(),
            'Timestamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'TradeAmt' => $amount,
        ];
        $args['Timestamp'] = $args['Timestamp']->getTimestamp();

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['linepay-refund'];
        } else {
            $url = $this->api_url['linepay-refund'];
        }

        $response = $this->link_server($url, $args, '1.0');
        return $this->get_decrypt_result($response, $args, 'LinepayRefund');
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::PAYMENT_TYPE')) {
            $args[$gateway::PAYMENT_TYPE] = 1;
            switch ($gateway::PAYMENT_TYPE) {
                case 'VACC':
                case 'CVS':
                    $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
                    $now->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $args['ExpireDate'] = $now->format('Ymd');
                    break;
                case 'CreditInst':
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        $args[$gateway::PAYMENT_TYPE] = implode(',', $gateway->number_of_periods);
                    }
                    break;
            }
        }

        return $args;
    }

    protected function get_decrypt_result($response, $args, $log_prefix = '')
    {
        if (is_wp_error($response)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if (!is_array($result)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        list($MerID, $HashKey, $HashIV) = RY_WT_WC_PAYUNi_Gateway::instance()->get_api_info();

        $ipn_info = $this->get_info_value($result);
        $self_hash_value = $this->generate_hash_value($ipn_info, $HashKey, $HashIV);
        if ($this->get_hash_value($result) !== $self_hash_value) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log($log_prefix . ' result check failed', WC_Log_Levels::WARNING, ['data' => $result, 'self' => $self_hash_value]);
            return;
        }

        $ipn_info = $this->args_decrypt($ipn_info, $HashKey, $HashIV);
        parse_str($ipn_info, $result);

        return $result;
    }
}
