<?php

defined('ABSPATH') or exit;

class RY_WT_WC_SmilePay_Gateway_Api extends RY_WT_SmilePay_Api
{
    protected static ?self $_instance = null;

    protected array $api_test_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
    ];

    protected array $api_url = [
        'checkout' => 'https://ssl.smse.com.tw/ezpos/mtmk_utf.asp',
        'api_checkout' => 'https://ssl.smse.com.tw/api/SPPayment.asp',
    ];

    public static function instance(): RY_WT_WC_SmilePay_Gateway_Api
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function checkout_form($order, $gateway)
    {
        $shipping_first = false;
        if (class_exists('RY_WT_WC_SmilePay_Shipping')) {
            foreach ($order->get_items('shipping') as $shipping_item) {
                $shipping_method = RY_WT_WC_SmilePay_Shipping::instance()->get_order_support_shipping($shipping_item);
                if ($shipping_method) {
                    $shipping_first = str_contains($shipping_method, '_cvs');
                    break;
                }
            }
        }
        if ($shipping_first) {
            $get_cvs = sanitize_key($_GET['get_cvs'] ?? '');
            if ($get_cvs !== 'true') {
                RY_WT_WC_SmilePay_Shipping_Api::instance()->csv_checkout_form($order);
                return;
            }
        }

        if ($gateway->get_code_mode) {
            $post_data = [
                'action' => 'RY_SmilePay_getcode',
                'id' => $order->get_id(),
                'key' => $order->get_order_key(),
                '_ajax_nonce' => wp_create_nonce('smilepay-getcode'),
            ];
            $this->submit_sctipt('$.ajax({
                type: "POST",
                url: wc_checkout_params.ajax_url,
                data: ' . wp_json_encode($post_data) . ',
                dataType: "text",
                success: function(result) {
                    window.location = result;
                }
            });');
            return;
        }

        list($url, $args) = $this->get_code_info($order, $gateway);
        RY_WT_WC_SmilePay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        $this->auto_submit_data($url, $args);

        do_action('ry_smilepay_gateway_checkout', $args, $order, $gateway);
    }

    public function get_code($order, $gateway)
    {
        $api_info = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();
        list($url, $args) = $this->get_code_info($order, $gateway);

        $args['Verify_key'] = $api_info['Verify_key'];
        RY_WT_WC_SmilePay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['data' => $args]);

        do_action('ry_smilepay_gateway_checkout', $args, $order, $gateway);

        if ($api_info['testmode']) {
            $url = $this->api_test_url['api_checkout'];
        } else {
            $url = $this->api_url['api_checkout'];
        }

        $response = $this->link_server($url, $args);
        if (is_wp_error($response)) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment POST failed', WC_Log_Levels::ERROR, ['info' => $response->get_error_messages()]);
            return false;
        }

        if (wp_remote_retrieve_response_code($response) != '200') {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment POST HTTP status error', WC_Log_Levels::ERROR, ['code' => wp_remote_retrieve_response_code($response)]);
            return false;
        }

        $ipn_info = wp_remote_retrieve_body($response);
        $info_value = @simplexml_load_string($ipn_info);
        if (!$info_value) {
            RY_WT_WC_SmilePay_Gateway::instance()->log('Payment code POST result parse failed', WC_Log_Levels::WARNING, ['data' => $ipn_info]);
            return false;
        }

        RY_WT_WC_SmilePay_Gateway::instance()->log('Shipping code POST result', WC_Log_Levels::INFO, ['data' => $info_value]);

        if ((string) $info_value->Status != '1') {
            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$d Error messade ID */
                __('Get Smilepay code error: %1$s (%2$d)', 'ry-woocommerce-tools'),
                $info_value->Desc,
                $info_value->Status,
            ));
            return false;
        }

        $transaction_ID = (string) $order->get_transaction_id();
        if ($transaction_ID === '' || $transaction_ID != $this->get_transaction_id($info_value)) {
            $order->set_transaction_id($this->get_transaction_id($info_value));
            $order->update_meta_data('_smilepay_payment_type', $args['Pay_zg']);
            $order->save();
            $order = wc_get_order($order->get_id());
        }

        switch ($gateway::PAYMENT_TYPE) {
            case 2:
                $order->update_meta_data('_smilepay_atm_BankCode', (string) $info_value->AtmBankNo);
                $order->update_meta_data('_smilepay_atm_vAccount', (string) $info_value->AtmNo);
                $order->update_meta_data('_smilepay_atm_ExpireDate', (string) $info_value->PayEndDate);
                $order->update_status('on-hold');
                break;
            case 3:
                $order->update_meta_data('_smilepay_barcode_Barcode1', (string) $info_value->Barcode1);
                $order->update_meta_data('_smilepay_barcode_Barcode2', (string) $info_value->Barcode2);
                $order->update_meta_data('_smilepay_barcode_Barcode3', (string) $info_value->Barcode3);
                $order->update_meta_data('_smilepay_barcode_ExpireDate', (string) $info_value->PayEndDate);
                $order->update_status('on-hold');
                break;
            case 4:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $info_value->IbonNo);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $info_value->PayEndDate);
                $order->update_status('on-hold');
                break;
            case 6:
                $order->update_meta_data('_smilepay_cvs_PaymentNo', (string) $info_value->FamiNO);
                $order->update_meta_data('_smilepay_cvs_ExpireDate', (string) $info_value->PayEndDate);
                $order->update_status('on-hold');
                break;
        }
    }

    protected function get_code_info($order, $gateway)
    {
        $notify_url = WC()->api_request_url('ry_smilepay_callback', true);

        $api_info = RY_WT_WC_SmilePay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name($api_info['itemname'], $order);
        $item_name = mb_substr($item_name, 0, 40);

        $args = [
            'Dcvc' => $api_info['Dcvc'],
            'Rvg2c' => $api_info['Rvg2c'],
            'Od_sob' => $item_name,
            'Data_id' => $this->generate_trade_no($order->get_id(), $api_info['prefix']),
            'Amount' => (int) ceil($order->get_total()),
            'Roturl' => $notify_url,
            'Roturl_status' => 'RY_SmilePay',
        ];

        switch (get_locale()) {
            case 'zh_HK':
            case 'zh_TW':
                break;
            case 'en_US':
            case 'en_AU':
            case 'en_CA':
            case 'en_GB':
            default:
                $args['Language'] = 'EN';
                break;
        }

        $args = $this->add_type_info($args, $order, $gateway);

        $order->update_meta_data('_smilepay_Data_id', $args['Data_id']);
        $order->save();

        if ($api_info['testmode']) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        return [$url, $args];
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::PAYMENT_TYPE')) {
            $args['Pay_zg'] = $gateway::PAYMENT_TYPE;

            switch ($gateway::PAYMENT_TYPE) {
                case '2':
                case '3':
                    $date = new DateTime('now', new DateTimeZone('Asia/Taipei'));
                    $date->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $args['Deadline_date'] = $date->format('Y/m/d');
                    break;
                case '4':
                case '6':
                    $date = new DateTime('now', new DateTimeZone('Asia/Taipei'));
                    $date->add(new DateInterval('PT' . $gateway->expire_date . 'M'));
                    $args['Deadline_date'] = $date->format('Y/m/d');
                    $args['Deadline_time'] = $date->format('H:i:s');
                    break;
            }
        }

        return $args;
    }
}
