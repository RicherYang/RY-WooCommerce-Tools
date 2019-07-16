<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Gateway_Response extends RY_ECPay_Gateway_Api {
	public static function init($gateway_id) {
		add_action('woocommerce_api_request', [__CLASS__, 'set_do_die']);
		add_action('woocommerce_api_ry_ecpay_callback', [__CLASS__, 'check_callback']);
		add_action('woocommerce_thankyou_' . $gateway_id, [__CLASS__, 'check_callback']);

		add_action('valid_ecpay_callback_request', [__CLASS__, 'doing_callback']);
	}

	public static function check_callback() {
		if( !empty($_POST) ) {
			$ipn_info = wp_unslash($_POST);
			if( self::ipn_request_is_valid($ipn_info) ) {
				do_action('valid_ecpay_callback_request', $ipn_info);
			} else {
				self::die_error();
			}
		}
	}

	protected static function ipn_request_is_valid($ipn_info) {
		RY_ECPay_Gateway::log('IPN request: ' . var_export($ipn_info, true));

		list($MerchantID, $HashKey, $HashIV) = RY_ECPay_Gateway::get_ecpay_api_info();

		$check_value = self::get_check_value($ipn_info);
		$ipn_info_check_value = self::generate_check_value($ipn_info, $HashKey, $HashIV, 'sha256');
		if( $check_value == $ipn_info_check_value ) {
			return true;
		} else {
			RY_ECPay_Gateway::log('IPN request check failed. Response:' . $check_value . ' Self:' . $ipn_info_check_value, 'error');
			return false;
		}
	}

	public static function doing_callback($ipn_info) {
		$order_id = self::get_order_id($ipn_info, RY_WT::get_option('ecpay_gateway_order_prefix'));
		if( $order = wc_get_order($order_id) ) {
			$payment_status = self::get_status($ipn_info);
			RY_ECPay_Gateway::log('Found order #' . $order->get_id() . ' Payment status: ' . $payment_status);

			if( (string) $order->get_transaction_id() == '' ) {
				list($payment_type, $payment_subtype) = self::get_payment_info($ipn_info);
				$order->set_transaction_id(self::get_transaction_id($ipn_info));
				$order->update_meta_data('_ecpay_payment_type', $payment_type);
				$order->update_meta_data('_ecpay_payment_subtype', $payment_subtype);
				$order->save();
			}
			
			if( method_exists(__CLASS__, 'payment_status_' . $payment_status) ) {
				call_user_func([__CLASS__, 'payment_status_' . $payment_status], $order, $ipn_info);
			} else {
				self::payment_status_unknow($order, $ipn_info, $payment_status);
			}

			do_action('ry_ecpay_gateway_response_status_' . $payment_status, $ipn_info, $order);
			do_action('ry_ecpay_gateway_response', $ipn_info, $order);

			self::die_success();
		}

		RY_ECPay_Gateway::log('Order not found', 'error');
		self::die_error();
	}

	protected static function get_payment_info($ipn_info) {
		if( isset($ipn_info['PaymentType']) ) {
			$payment_type = $ipn_info['PaymentType'];
			$payment_type = explode('_', $payment_type);
			if( count($payment_type) == 1 ) {
				$payment_type[] = '';
			}
			return $payment_type;
		}
		return false;
	}

	protected static function payment_status_1($order, $ipn_info) {
		$order->add_order_note(__('Payment completed', 'ry-woocommerce-tools'));
		$order->payment_complete();
	}

	protected static function payment_status_2($order, $ipn_info) {
		$expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

		$order->update_meta_data('_ecpay_atm_BankCode', $ipn_info['BankCode']);
		$order->update_meta_data('_ecpay_atm_vAccount', $ipn_info['vAccount']);
		$order->update_meta_data('_ecpay_atm_ExpireDate', $expireDate->format(DATE_ATOM));
		$order->save_meta_data();

		$order->update_status('on-hold');
	}

	protected static function payment_status_10100073($order, $ipn_info) {
		list($payment_type, $payment_subtype) = self::get_payment_info($ipn_info);
		$expireDate = new DateTime($ipn_info['ExpireDate'], new DateTimeZone('Asia/Taipei'));

		if( $payment_type == 'CVS' ) {
			$order->update_meta_data('_ecpay_cvs_PaymentNo', $ipn_info['PaymentNo']);
			$order->update_meta_data('_ecpay_cvs_ExpireDate', $expireDate->format(DATE_ATOM));
		} else {
			$order->update_meta_data('_ecpay_barcode_Barcode1', $ipn_info['Barcode1']);
			$order->update_meta_data('_ecpay_barcode_Barcode2', $ipn_info['Barcode2']);
			$order->update_meta_data('_ecpay_barcode_Barcode3', $ipn_info['Barcode3']);
			$order->update_meta_data('_ecpay_barcode_ExpireDate', $expireDate->format(DATE_ATOM));
		}
		$order->save_meta_data();

		$order->update_status('on-hold');
	}

	protected static function payment_status_unknow($order, $ipn_info, $payment_status) {
		RY_ECPay_Gateway::log('Unknow status: ' . self::get_status($ipn_info) . '(' . self::get_status_msg($ipn_info) . ')');
		$order->update_status('failed', sprintf(
			/* translators: 1: Error status code 2: Error status message */
			__('Payment failed: %1$s (%2$s)', 'ry-woocommerce-tools'),
			self::get_status($ipn_info),
			self::get_status_msg($ipn_info)
		));
	}
}
