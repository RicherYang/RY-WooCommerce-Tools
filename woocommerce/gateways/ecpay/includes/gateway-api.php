<?php

class RY_WT_WC_ECPay_Gateway_Api extends RY_WT_WC_ECPay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'sptoken' => 'https://payment-stage.ecpay.com.tw/SP/CreateTrade',
    ];
    protected $api_url = [
        'checkout' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'sptoken' => 'https://payment.ecpay.com.tw/SP/CreateTrade',
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
            'ClientRedirectURL' => $return_url
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

        if (RY_WT_WC_ECPay_Gateway::instance()->testmode) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }
        echo '<form method="post" id="ry-ecpay-form" action="' . esc_url($url) . '">';
        foreach ($args as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';
        $this->submit_sctipt('document.getElementById("ry-ecpay-form").submit();', $order);

        do_action('ry_ecpay_gateway_checkout', $args, $order, $gateway);
    }

    protected function add_type_info($args, $order, $gateway)
    {
        switch ($gateway::Payment_Type) {
            case 'Credit':
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
                            $number_of_periods
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
