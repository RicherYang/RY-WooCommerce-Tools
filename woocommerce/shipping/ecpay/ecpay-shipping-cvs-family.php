<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_CVS_Family extends RY_ECPay_Shipping_CVS {
	public static $LogisticsType = 'CVS';
	public static $LogisticsSubType = 'FAMI';

	public function __construct($instance_id = 0) {
		$this->id = 'ry_ecpay_shipping_cvs_family';
		$this->instance_id = absint($instance_id);
		$this->method_title = __('ECPay shipping CVS Family', 'ry-woocommerce-tools');
		$this->method_description = '';
		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->instance_form_fields = include(RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping-cvs.php');
		$this->instance_form_fields['cost']['default'] = 65;

		$this->init();
	}
}
