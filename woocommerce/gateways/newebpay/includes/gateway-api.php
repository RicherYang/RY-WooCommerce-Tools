<?php

class RY_WT_WC_NewebPay_Gateway_Api extends RY_WT_NewebPay_Api
{
    protected static $_instance = null;

    protected $api_test_url = [
        'checkout' => 'https://ccore.newebpay.com/MPG/mpg_gateway',
    ];

    protected $api_url = [
        'checkout' => 'https://core.newebpay.com/MPG/mpg_gateway',
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

        list($MerchantID, $HashKey, $HashIV) = RY_WT_WC_NewebPay_Gateway::instance()->get_api_info();

        $item_name = $this->get_item_name(RY_WT::get_option('payment_item_name', ''), $order);
        $item_name = mb_substr($item_name, 0, 40);

        $args = [
            'MerchantID' => $MerchantID,
            'RespondType' => 'JSON',
            'TimeStamp' => new DateTime('now', new DateTimeZone('Asia/Taipei')),
            'Version' => '2.0',
            'MerchantOrderNo' => $this->generate_trade_no($order->get_id(), RY_WT::get_option('newebpay_gateway_order_prefix')),
            'Amt' => (int) ceil($order->get_total()),
            'ItemDesc' => $item_name,
            'ReturnURL' => $return_url,
            'NotifyURL' => $notify_url,
            'CustomerURL' => $return_url,
            'Email' => $order->get_billing_email(),
            'EmailModify' => 0,
            'CREDIT' => 0,
            'ANDROIDPAY' => 0,
            'SAMSUNGPAY' => 0,
            'LINEPAY' => 0,
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
            'FULA' => 0,
            'EZPAY' => 0,
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
                $args['Language'] = 'jp';
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
            'MerchantID' => $MerchantID,
            'TradeInfo' => $this->args_encrypt($args, $HashKey, $HashIV),
            'Version' => '2.0',
            'EncryptType' => 0,
        ];
        $form_data['TradeSha'] = $this->generate_hash_value($form_data['TradeInfo'], $HashKey, $HashIV);
        RY_WT_WC_NewebPay_Gateway::instance()->log('Generating payment by ' . $gateway->id . ' for #' . $order->get_id(), WC_Log_Levels::INFO, ['form' => $form_data, 'data' => $args]);

        $order->update_meta_data('_newebpay_MerchantOrderNo', $args['MerchantOrderNo']);
        $order->save();

        if (RY_WT_WC_NewebPay_Gateway::instance()->is_testmode()) {
            $url = $this->api_test_url['checkout'];
        } else {
            $url = $this->api_url['checkout'];
        }

        echo '<form method="post" id="ry-newebpay-form" action="' . esc_url($url) . '">';
        foreach ($form_data as $key => $value) {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
        echo '</form>';
        $this->submit_sctipt('document.getElementById("ry-newebpay-form").submit();', $order);

        do_action('ry_newebpay_gateway_checkout', $args, $order, $gateway);
    }

    protected function add_type_info($args, $order, $gateway)
    {
        if (defined(get_class($gateway) . '::Payment_Type')) {
            if (isset($args[$gateway::Payment_Type])) {
                $args[$gateway::Payment_Type] = 1;
            }
            switch ($gateway::Payment_Type) {
                case 'VACC':
                case 'CVS':
                case 'BARCODE':
                    $now = new DateTime('', new DateTimeZone('Asia/Taipei'));
                    $now->add(new DateInterval('P' . $gateway->expire_date . 'D'));
                    $args['ExpireDate'] = $now->format('Ymd');
                    break;
                case 'InstFlag':
                    if (isset($gateway->number_of_periods) && !empty($gateway->number_of_periods)) {
                        if (is_array($gateway->number_of_periods)) {
                            $number_of_periods = (int) $order->get_meta('_newebpay_payment_number_of_periods', true);
                            if (!in_array($number_of_periods, $gateway->number_of_periods)) {
                                $number_of_periods = 0;
                            }
                        } else {
                            $number_of_periods = (int) $gateway->number_of_periods;
                        }
                        if (in_array($number_of_periods, [3, 6, 12, 18, 24, 30])) {
                            $args['InstFlag'] = $number_of_periods;

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

        $items_shipping = $order->get_items('shipping');
        $items_shipping = array_shift($items_shipping);
        if ($items_shipping) {
            if ($items_shipping->get_method_id() == 'ry_newebpay_shipping_cvs') {
                if ($gateway->id == 'cod') {
                    $args['CVSCOM'] = 2;
                } else {
                    $args['CVSCOM'] = 1;
                }
            }
        }

        return $args;
    }
}
