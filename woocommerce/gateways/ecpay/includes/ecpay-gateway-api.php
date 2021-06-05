<?php
class RY_ECPay_Gateway_Api extends RY_ECPay
{
    public static $api_test_url = [
        'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'sptoken' => 'https://payment-stage.ecpay.com.tw/SP/CreateTrade',
    ];
    public static $api_url = [
        'checkout' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
        'query' => 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
        'sptoken' => 'https://payment.ecpay.com.tw/SP/CreateTrade',
    ];

    public static function checkout_form($order, $gateway)
    {
        RY_ECPay_Gateway::log('Generating payment form by ' . $gateway->id . ' for #' . $order->get_order_number());

        $notify_url = WC()->api_request_url('ry_ecpay_callback', true);
        $return_url = $gateway->get_return_url($order);

        list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

        $args = [
            'MerchantID' => $MerchantID,
            'MerchantTradeNo' => self::generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_gateway_order_prefix')),
            'MerchantTradeDate' => new DateTime('', new DateTimeZone('Asia/Taipei')),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) ceil($order->get_total()),
            'TradeDesc' => mb_substr(get_bloginfo('name'), 0, 100),
            'ItemName' => self::get_item_name($order),
            'ReturnURL' => $notify_url,
            'ChoosePayment' => $gateway->payment_type,
            'ClientBackURL' => $return_url,
            'OrderResultURL' => $return_url,
            'NeedExtraPaidInfo' => 'Y',
            'IgnorePayment' => '',
            'EncryptType' => 1,
            'PaymentInfoURL' => $notify_url,
            'ClientRedirectURL' => $return_url
        ];
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

        $args = self::add_type_info($args, $order, $gateway);
        $args = self::add_check_value($args, $HashKey, $HashIV, 'sha256');
        RY_ECPay_Gateway::log('Checkout POST: ' . var_export($args, true));

        $order->update_meta_data('_ecpay_MerchantTradeNo', $args['MerchantTradeNo']);
        $order->save_meta_data();

        if ('yes' === RY_WT::get_option('ecpay_gateway_testmode', 'yes')) {
            $url = self::$api_test_url['checkout'];
        } else {
            $url = self::$api_url['checkout'];
        }
        echo '<form method="post" id="ry-ecpay-form" action="' . esc_url($url) . '" style="display:none;">';
        foreach ($args as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';

        wc_enqueue_js(
            '$.blockUI({
    message: "' . __('Please wait.<br>Getting checkout info.', 'ry-woocommerce-tools') . '",
    baseZ: 99999,
    overlayCSS: {
        background: "#000",
        opacity: 0.4
    },
    css: {
        "font-size": "1.5em",
        padding: "1.5em",
        textAlign: "center",
        border: "3px solid #aaa",
        backgroundColor: "#fff",
    }
});
$("#ry-ecpay-form").submit();'
        );

        do_action('ry_ecpay_gateway_checkout', $args, $order, $gateway);
    }

    protected static function add_type_info($args, $order, $gateway)
    {
        switch ($gateway->payment_type) {
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

    protected static function get_item_name($order)
    {
        $item_name = '';
        if (count($order->get_items())) {
            foreach ($order->get_items() as $item) {
                $item_name .= str_replace('#', '', trim($item->get_name())) . '#';
                if (strlen($item_name) > 200) {
                    break;
                }
            }
        }
        $item_name = rtrim($item_name, '#');
        return $item_name;
    }
}
