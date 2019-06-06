<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Api extends RY_ECPay {
	public static $api_test_url = [
		'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
		'query' => 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
		'sptoken' => 'https://payment-stage.ecpay.com.tw/SP/CreateTrade',
		'inpay_js' => 'https://payment-stage.ecpay.com.tw/Scripts/SP/ECPayPayment_1.0.0.js'
	];
	public static $api_url = [
		'checkout' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
		'query' => 'https://payment.ecpay.com.tw/Cashier/QueryTradeInfo/V5',
		'sptoken' => 'https://payment.ecpay.com.tw/SP/CreateTrade',
		'inpay_js' => 'https://payment.ecpay.com.tw/Scripts/SP/ECPayPayment_1.0.0.js'
	];

	public static function checkout_form($order, $gateway) {
		RY_ECPay_Gateway::log('Generating payment form for order #' . $order->get_order_number());

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
		if( $gateway->payment_type == 'Credit' ) {
			switch( get_locale() ) {
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
		}

		$args = self::add_type_info($args, $order, $gateway);
		$args = self::add_check_value($args, $HashKey, $HashIV, 'sha256');
		RY_ECPay_Gateway::log('Checkout POST: ' . var_export($args, true));

		$order->update_meta_data('_ecpay_MerchantTradeNo', $args['MerchantTradeNo']);
		$order->save_meta_data();

		if( 'yes' === RY_WT::get_option('ecpay_gateway_testmode', 'yes') ) {
			$url = self::$api_test_url['checkout'];
		} else {
			$url = self::$api_url['checkout'];
		}
		echo '<form method="post" id="ry-ecpay-form" action="' . esc_url($url) . '" style="display:none;">';
		foreach( $args as $key => $value ) {
			echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
		}
		echo '</form>';

		wc_enqueue_js(
'$.blockUI({
	message: "' . __('Please wait transfer to checkout page.', 'ry-woocommerce-tools') . '",
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
$("#ry-ecpay-form").submit();');

		do_action('ry_ecpay_gateway_checkout', $args, $order, $gateway);
	}

	public static function inpay_checkout_form($order, $gateway) {
		RY_ECPay_Gateway::log('Generating inpay payment form for order #' . $order->get_order_number());

		$notify_url = WC()->api_request_url('ry_ecpay_callback', true);

		list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

		$args = [
			'MerchantID' => $MerchantID,
			'MerchantTradeNo' => self::generate_trade_no($order->get_id(), RY_WT::get_option('ecpay_gateway_order_prefix')),
			'MerchantTradeDate' => current_time('Y/m/d H:i:s'),
			'PaymentType' => 'aio',
			'TotalAmount' => (int) ceil($order->get_total()),
			'TradeDesc' => mb_substr(get_bloginfo('name'), 0, 100),
			'ItemName' => self::get_item_name($order),
			'ReturnURL' => $notify_url,
			'ChoosePayment' => $gateway->payment_type,
			'NeedExtraPaidInfo' => 'Y',
			'EncryptType' => 1,
			'PaymentInfoURL' => $notify_url
		];

		$args = self::add_type_info($args, $order, $gateway);
		$args = self::add_check_value($args, $HashKey, $HashIV, 'sha256');
		RY_ECPay_Gateway::log('Get SPToken POST: ' . var_export($args, true));

		$order->update_meta_data('_ecpay_MerchantTradeNo', $args['MerchantTradeNo']);
		$order->save_meta_data();

		if( 'yes' === RY_WT::get_option('ecpay_gateway_testmode', 'yes') ) {
			$post_url = self::$api_test_url['sptoken'];
			$js_file = self::$api_test_url['inpay_js'];
		} else {
			$post_url = self::$api_url['sptoken'];
			$js_file = self::$api_url['inpay_js'];
		}

		wc_set_time_limit(40);
		$response = wp_remote_post($post_url, [
			'timeout' => 20,
			'body' => $args
		]);
		if( !is_wp_error($response) ) {
			if( $response['response']['code'] == '200' ) {
				RY_ECPay_Gateway::log('SPToken request result: ' . $response['body']);
				$token_info = json_decode($response['body'], true);
				if( is_array($token_info) ) {
					$check_value = self::get_check_value($token_info);
					$token_info_check_value = self::generate_check_value($token_info, $HashKey, $HashIV, 'sha256');
					if( $check_value == $token_info_check_value ) {
						if( self::get_status($token_info) == 1 ) {
							?>
							<script src="<?=$js_file ?>"
								data-MerchantID="<?=$token_info['MerchantID'] ?>"
								data-SPToken="<?=$token_info['SPToken'] ?>"
								data-PaymentType="<?=$gateway->inpay_payment_type ?>"
								data-CustomerBtn="1">
							</script>
							<button type="button" class="button alt" id="ecpay_checkout_btn" onclick="checkOut('<?=$gateway->inpay_payment_type ?>')"><?=$gateway->order_button_text ?></button>
							<?php
							wc_enqueue_js(
'function autoCheckOut() {
	var CheckMobile = new RegExp("android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino"),
		CheckMobile2 = new RegExp("mobile|mobi|nokia|samsung|sonyericsson|mot|blackberry|lg|htc|j2me|ucweb|opera mini|mobi|android|iphone");
	if( CheckMobile.test(navigator.userAgent) || CheckMobile2.test(navigator.userAgent.toLowerCase())) {
   } else {
   	$("#ecpay_checkout_btn").hide();
		if( $("#ecpay_checkout_btn").length ) {
			$("#ecpay_checkout_btn").click();
		} else {
			setTimeout(autoCheckOut, 100);
		}
	}
}
autoCheckOut();
window.addEventListener("message", function (e) {
	if( typeof e.data == "string" ) {
		var data = JSON.parse(e.data);
		if( typeof data == "object" && typeof data.MerchantTradeNo != "undefined" ) {
			location.href = "' . $gateway->get_return_url($order) . '";
		}
	}
});');
							do_action('ry_ecpay_gateway_checkout_inpay', $args, $order, $gateway);
						}
					} else {
						RY_ECPay_Gateway::log('SPToken request check failed. Response:' . $check_value . ' Self:' . $token_info_check_value, 'error');
					}
				}
			} else {
				RY_ECPay_Gateway::log('SPToken failed. Http code: ' . $response['response']['code'], 'error');
				self::checkout_form($order, $gateway);
			}
		} else {
			RY_ECPay_Gateway::log('SPToken failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
			self::checkout_form($order, $gateway);
		}
	}

	public static function query_info($order) {
		RY_ECPay_Gateway::log('Query payment info #' . $order->get_order_number());

		list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

		$args = [
			'MerchantID' => $MerchantID,
			'MerchantTradeNo' => $order->get_meta('_ecpay_MerchantTradeNo'),
			'TimeStamp' => new DateTime('', new DateTimeZone('Asia/Taipei'))
		];
		$args['TimeStamp'] = $args['TimeStamp']->format('U');

		$args = self::add_check_value($args, $HashKey, $HashIV, 'sha256');
		RY_ECPay_Gateway::log('Query POST: ' . var_export($args, true));

		if( 'yes' === RY_WT::get_option('ecpay_gateway_testmode', 'yes') ) {
			$post_url = self::$api_test_url['query'];
		} else {
			$post_url = self::$api_url['query'];
		}

		$response = wp_remote_post($post_url, [
			'timeout' => 20,
			'body' => $args
		]);
		if( !is_wp_error($response) ) {
			if( $response['response']['code'] == '200' ) {
				RY_ECPay_Gateway::log('Payment Query request result: ' . $response['body']);
			} else {
				RY_ECPay_Gateway::log('Payment Query failed. Http code: ' . $response['response']['code'], 'error');
			}
		} else {
			RY_ECPay_Gateway::log('Payment Query failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
		}
	}

	protected static function add_type_info($args, $order, $gateway) {
		switch( $gateway->payment_type ) {
			case 'Credit':
				if( isset($gateway->number_of_periods) && !empty($gateway->number_of_periods) ) {
					if( is_array($gateway->number_of_periods) ) {
						$number_of_periods = (int) $order->get_meta('_ecpay_payment_number_of_periods', true);
						if( !in_array($number_of_periods, $gateway->number_of_periods) ) {
							$number_of_periods = 0;
						}
					} else {
						$number_of_periods = (int) $gateway->number_of_periods;
					}
					if( in_array($number_of_periods, [3, 6, 12, 18, 24]) ) {
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
			case 'CVS':
				$args['StoreExpireDate'] = $gateway->expire_date;
				break;
		}
		return $args;
	}

	protected static function get_item_name($order) {
		$item_name = '';
		if( count($order->get_items()) ) {
			foreach( $order->get_items() as $item ) {
				$item_name .= trim($item->get_name()) . '#';
				if( strlen($item_name) > 200 ) {
					break;
				}
			}
		}
		$item_name = rtrim($item_name, '#');
		return $item_name;
	}
}
