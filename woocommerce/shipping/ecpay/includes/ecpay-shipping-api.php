<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_Api extends RY_ECPay {
	public static $api_test_url = [
		'map' => 'https://logistics-stage.ecpay.com.tw/Express/map',
		'create' => 'https://logistics-stage.ecpay.com.tw/Express/Create',
		'print_UNIMART' => 'https://logistics-stage.ecpay.com.tw/Express/PrintUniMartC2COrderInfo',
		'print_FAMI' => 'https://logistics-stage.ecpay.com.tw/Express/PrintFAMIC2COrderInfo',
		'print_HILIFE' => 'https://logistics-stage.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo',
		'print_B2C' => 'https://logistics-stage.ecpay.com.tw/helper/printTradeDocument'
	];
	public static $api_url = [
		'map' => 'https://logistics.ecpay.com.tw/Express/map',
		'create' => 'https://logistics.ecpay.com.tw/Express/Create',
		'print_UNIMART' => 'https://logistics.ecpay.com.tw/Express/PrintUniMartC2COrderInfo',
		'print_FAMI' => 'https://logistics.ecpay.com.tw/Express/PrintFAMIC2COrderInfo',
		'print_HILIFE' => 'https://logistics.ecpay.com.tw/Express/PrintHILIFEC2COrderInfo',
		'print_B2C' => 'https://logistics.ecpay.com.tw/helper/printTradeDocument'
	];

	public static function get_cvs_code($order_id, $collection = false) {
		if( $order = wc_get_order($order_id) ) {
			$item_names = [];
			if( count($order->get_items()) ) {
				foreach( $order->get_items() as $item ) {
					$item_names[] = trim($item->get_name());
				}
			}
			$item_names = implode(' ', $item_names);
			$item_names = str_replace(['^','\'','`','!','@','＠','#','%','&','*','+','\\','"','<','>','|','_','[',']'], '', $item_names);
			$item_names = mb_substr($item_names, 0, 25);

			foreach( $order->get_items('shipping') as $item_id => $item ) {
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
				if( $shipping_method !== false ) {
					$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
					if( !is_array($cvs_info_list) ) {
						$cvs_info_list = [];
					}

					$get_count = 1;
					if( count($cvs_info_list) == 0 ) {
						$get_count = (int) $item->get_meta('no_count');
					}
					if( $get_count < 1 ) {
						$get_count = 1;
					}

					$method_class = RY_ECPay_Shipping::$support_methods[$shipping_method];

					list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

					$total = ceil($order->get_total());
					if( $total > 20000 ) {
						$total = 19999;
					}

					$notify_url = WC()->api_request_url('ry_ecpay_shipping_callback', true);

					RY_ECPay_Shipping::log('Generating shipping for order #' . $order->get_order_number() . ' with ' . $get_count . ' times');

					$args = [
						'MerchantID' => $MerchantID,
						'LogisticsType' => $method_class::$LogisticsType,
						'LogisticsSubType' => $method_class::$LogisticsSubType . (('C2C' == $CVS_type) ? 'C2C' : ''),
						'GoodsAmount' => (int) $total,
						'GoodsName' => $item_names,
						'SenderName' => get_option(RY_WT::$option_prefix . 'ecpay_shipping_sender_name'),
						'SenderPhone' => get_option(RY_WT::$option_prefix . 'ecpay_shipping_sender_phone'),
						'SenderCellPhone' => get_option(RY_WT::$option_prefix . 'ecpay_shipping_sender_cellphone'),
						'ReceiverName' => $order->get_shipping_last_name() . $order->get_shipping_first_name(),
						'ReceiverCellPhone' => $order->get_meta('_shipping_phone'),
						'ServerReplyURL' => $notify_url,
						'LogisticsC2CReplyURL' => $notify_url,
					];

					if( count($cvs_info_list) == 0 ) {
						if( $order->get_payment_method() == 'cod' ) {
							$args['IsCollection'] = 'Y';
							$args['CollectionAmount'] = $args['GoodsAmount'];
						} else {
							$args['IsCollection'] = 'N';
							$args['CollectionAmount'] = 0;
						}
					}
					if( $collection == true ) {
						$args['IsCollection'] = 'Y';
						$args['CollectionAmount'] = $args['GoodsAmount'];
					}

					if( $method_class::$LogisticsType == 'CVS' ) {
						$args['ReceiverStoreID'] = $order->get_meta('_shipping_cvs_store_ID');
					}

					if( RY_ECPay_Shipping::$testmode ) {
						$post_url = self::$api_test_url['create'];
					} else {
						$post_url = self::$api_url['create'];
					}

					for( $i = 0; $i < $get_count; ++$i ) {
						$create_datetime = new DateTime('', new DateTimeZone('Asia/Taipei'));
						$args['MerchantTradeDate'] = $create_datetime->format('Y/m/d H:i:s');
						$args['MerchantTradeNo'] = self::generate_trade_no($order->get_id(), get_option(RY_WT::$option_prefix . 'ecpay_shipping_order_prefix'));
						if( $i > 01 ) {
							$args['IsCollection'] = 'N';
							$args['CollectionAmount'] = 0;
						}

						$args = self::add_check_value($args, $HashKey, $HashIV, 'md5');
						RY_ECPay_Shipping::log('Shipping POST: ' . var_export($args, true));

						wc_set_time_limit(40);
						$response = wp_remote_post($post_url, [
							'timeout' => 20,
							'body' => $args
						]);
						if( !is_wp_error($response) ) {
							if( $response['response']['code'] == '200' ) {
								RY_ECPay_Shipping::log('Shipping request result: ' . $response['body']);
								$body = explode('|', $response['body']);
								if( count($body) == 2 ) {
									if( $body[0] == '1' ) {
										parse_str($body[1], $result);
										if( is_array($result) ) {
											$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
											if( !is_array($cvs_info_list) ) {
												$cvs_info_list = [];
											}
											if( !isset($cvs_info_list[$result['AllPayLogisticsID']]) ) {
												$cvs_info_list[$result['AllPayLogisticsID']] = [];
											}
											$cvs_info_list[$result['AllPayLogisticsID']]['ID'] = $result['AllPayLogisticsID'];
											$cvs_info_list[$result['AllPayLogisticsID']]['PaymentNo'] = $result['CVSPaymentNo'];
											$cvs_info_list[$result['AllPayLogisticsID']]['ValidationNo'] = $result['CVSValidationNo'];
											$cvs_info_list[$result['AllPayLogisticsID']]['store_ID'] = $args['ReceiverStoreID'];
											$cvs_info_list[$result['AllPayLogisticsID']]['status'] = self::get_status($result);
											$cvs_info_list[$result['AllPayLogisticsID']]['status_msg'] = self::get_status_msg($result);
											$cvs_info_list[$result['AllPayLogisticsID']]['create'] = $create_datetime->format(DATE_ATOM);
											$cvs_info_list[$result['AllPayLogisticsID']]['edit'] = (string) new WC_DateTime();
											$cvs_info_list[$result['AllPayLogisticsID']]['amount'] = $args['GoodsAmount'];
											$cvs_info_list[$result['AllPayLogisticsID']]['IsCollection'] = $args['IsCollection'];

											$order->update_meta_data('_shipping_cvs_info', $cvs_info_list);
											$order->save_meta_data();

											do_action('ry_ecpay_shipping_get_cvs_no', $result, $cvs_info_list[$result['AllPayLogisticsID']]);
										}
									} else {
										$order->add_order_note(sprintf(__('Get shipping code error: %s', 'ry-woocommerce-tools'), $body[1]));
									}
								}
							} else {
								RY_ECPay_Shipping::log('Shipping failed. Http code: ' . $response['response']['code'], 'error');
							}
						} else {
							RY_ECPay_Shipping::log('Shipping failed. POST error: ' . implode("\n", $response->get_error_messages()), 'error');
						}
					}

					do_action('ry_ecpay_shipping_get_all_cvs_no', $cvs_info_list);
				}
			}
		}
	}

	public static function get_cvs_code_cod($order_id) {
		self::get_cvs_code($order_id, true);
	}

	public static function print_info() {
		$order_ID = (int) $_GET['orderid'];
		$Logistics_ID = (int) $_GET['id'];

		if( $order = wc_get_order($order_ID) ) {
			foreach( $order->get_items('shipping') as $item_id => $item ) {
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
				if( $shipping_method !== false ) {
					$cvs_info_list = $order->get_meta('_shipping_cvs_info', true);
					if( is_array($cvs_info_list) ) {
						list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();
						foreach( $cvs_info_list as $info ) {
							if( $info['ID'] == $Logistics_ID ) {
								$args = [
									'MerchantID' => $MerchantID,
									'AllPayLogisticsID' => $info['ID'],
								];
								if( $CVS_type == 'C2C' ) {
									$args['CVSPaymentNo'] = $info['PaymentNo'];
									$args['CVSValidationNo'] = $info['ValidationNo'];
								}
								$args = self::add_check_value($args, $HashKey, $HashIV, 'md5');

								if( RY_ECPay_Shipping::$testmode ) {
									if( $CVS_type == 'C2C' ) {
										$post_url = self::$api_test_url['print_' . $shipping_method::$LogisticsSubType];
									} else {
										$post_url = self::$api_test_url['print_B2C'];
									}
								} else {
									if( $CVS_type == 'C2C' ) {
										$post_url = self::$api_url['print_' . $shipping_method::$LogisticsSubType];
									} else {
										$post_url = self::$api_url['print_B2C'];
									}
								}

								wc_set_time_limit(40);
								$response = wp_remote_post($post_url, [
									'timeout' => 20,
									'body' => $args
								]);
								if( $response['response']['code'] == '200' ) {
									echo($response['body']);
								}
							}
						}
					}
				}
			}
		}
		wp_die();
	}
}
