<?php

defined('ABSPATH') or exit;

class RY_WT_WC_PAYUNi_Gateway_Api extends RY_WT_PAYUNi_Api
{
    protected static ?self $_instance = null;

    protected array $api_test_url = [
        'checkout' => 'https://sandbox-api.payuni.com.tw/api/upp',
        'query' => 'https://sandbox-api.payuni.com.tw/api/trade/query',
    ];

    protected array $api_url = [
        'checkout' => 'https://api.payuni.com.tw/api/upp',
        'query' => 'https://api.payuni.com.tw/api/trade/query',
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
        $form_data = [
            'MerID' => $MerID,
            'EncryptInfo' => $this->args_encrypt($args, $HashKey, $HashIV),
            'Version' => '2.0',
        ];
        $form_data['HashInfo'] = $this->generate_hash_value($form_data['EncryptInfo'], $HashKey, $HashIV);

        if (RY_WT_WC_PAYUNi_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['query'];
        } else {
            $url = $this->api_url['query'];
        }

        $response = $this->link_server($url, $form_data);
        if (is_wp_error($response)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Query failed', WC_Log_Levels::ERROR, ['data' => $args, 'info' => $response->get_error_messages()]);
            return;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Query HTTP status error', WC_Log_Levels::ERROR, ['data' => $args, 'code' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        if (!is_array($result)) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Query result parse failed', WC_Log_Levels::WARNING, ['data' => $args, 'result' => wp_remote_retrieve_body($response)]);
            return;
        }

        $ipn_info = $this->get_info_value($result);
        $self_hash_value = $this->generate_hash_value($ipn_info, $HashKey, $HashIV);
        if ($this->get_hash_value($result) !== $self_hash_value) {
            RY_WT_WC_PAYUNi_Gateway::instance()->log('Query result check failed', WC_Log_Levels::WARNING, ['data' => $result, 'self' => $self_hash_value]);
            return;
        }

        $ipn_info = $this->args_decrypt($ipn_info, $HashKey, $HashIV);
        parse_str($ipn_info, $result);

        return $result;
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::Payment_Type')) {
            $args[$gateway::Payment_Type] = 1;
            switch ($gateway::Payment_Type) {
                case 'VACC':
                case 'CVS':
                    $now = new DateTime('', new DateTimeZone('Asia/Taipei'));
                    $now->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $args['ExpireDate'] = $now->format('Ymd');
                    break;
                case 'CreditInst':
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        if (is_array($gateway->number_of_periods)) {
                            $number_of_periods = (int) $order->get_meta('_payuni_payment_number_of_periods', true);
                            if (!in_array($number_of_periods, $gateway->number_of_periods)) {
                                $number_of_periods = 0;
                            }
                        } else {
                            $number_of_periods = (int) $gateway->number_of_periods;
                        }
                        if (in_array($number_of_periods, [3, 6, 9, 12, 18, 24, 30])) {
                            $args[$gateway::Payment_Type] = $number_of_periods;

                            $order->add_order_note(sprintf(
                                /* translators: %d number of periods */
                                __('Credit installment to %d', 'ry-woocommerce-tools'),
                                $number_of_periods,
                            ));
                            $order->save();
                        }
                    }
                    break;
            }
        }

        return $args;
    }
}
