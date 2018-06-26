<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

class RY_ECPay_Shipping_CVS extends WC_Shipping_Method {
	public function init() {
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->tax_status = $this->get_option('tax_status');
		$this->cost = $this->get_option('cost');
		$this->cost_requires = $this->get_option('cost_requires');
		$this->min_amount = $this->get_option('min_amount', 0);
		$this->weight_plus_cost = $this->get_option('weight_plus_cost', 0);

		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
		
		add_action('woocommerce_update_order', array($this, 'save_order_update'));
	}

	public function get_instance_form_fields() {
		static $is_print = array();
		if( is_admin() ) {
			if( !isset($is_print[$this->id]) ) {
				$is_print[$this->id] = true;
				wc_enqueue_js(
'jQuery(function($) {
	function RYECPayShowHide' . $this->id . 'MinAmountField(el) {
		var form = $(el).closest("form");
		var minAmountField = $("#woocommerce_' . $this->id . '_min_amount", form).closest("tr");
		if( "min_amount" === $(el).val() ) {
			minAmountField.show();
		} else {
			minAmountField.hide();
		}
	}
	$(document.body).on("change", "#woocommerce_' . $this->id . '_cost_requires", function(){
		RYECPayShowHide' . $this->id . 'MinAmountField(this);
	}).change();
	$(document.body).on("wc_backbone_modal_loaded", function(evt, target) {
		if("wc-modal-shipping-method-settings" === target ) {
			RYECPayShowHide' . $this->id . 'MinAmountField($("#wc-backbone-modal-dialog #woocommerce_' . $this->id . '_cost_requires", evt.currentTarget));
		}
	});
});');
			}
		}
		return parent::get_instance_form_fields();
	}

	function is_available($package) {
		$is_available = false;

		list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();
		if( !empty($MerchantID) && !empty($HashKey) && !empty($HashIV) ) {
			$is_available = true;
		}

		if( $is_available ) {
			$shipping_classes = WC()->shipping->get_shipping_classes();
			if( !empty($shipping_classes) ) {
				$found_shipping_class = array();
				foreach( $package['contents'] as $item_id => $values ) {
					if( $values['data']->needs_shipping() ) {
						$shipping_class_slug = $values['data']->get_shipping_class();
						$shipping_class = get_term_by('slug', $shipping_class_slug, 'product_shipping_class');
						if( $shipping_class && $shipping_class->term_id ) {
							$found_shipping_class[$shipping_class->term_id] = true;
						}
					}
				}
				foreach( $found_shipping_class as $shipping_class_term_id => $value ) {
					if( 'yes' != $this->get_option('class_available_' . $shipping_class_term_id, 'yes') ) {
						$is_available = false;
						break;
					}
				}
			}
		}

	 	return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
	}

	public function calculate_shipping($package = array()) {
		$rate = array(
			'id' => $this->get_rate_id(),
			'label' => $this->title,
			'cost' => $this->cost,
			'package' => $package,
			'meta_data' => array(
				'no_count' => 1
			)
		);

		if( $this->cost_requires == 'min_amount' ) {
			$total = WC()->cart->get_displayed_subtotal();
			if( 'incl' === WC()->cart->tax_display_cart ) {
				$total = round($total - (WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total()), wc_get_price_decimals());
			} else {
				$total = round($total - WC()->cart->get_cart_discount_total(), wc_get_price_decimals());
			}
			if ( $total >= $this->min_amount ) {
				$rate['cost'] = 0;
			}
		}

		if( $this->weight_plus_cost > 0 ) {
			$total = WC()->cart->get_cart_contents_weight();
			if( $total > 0 ) {
				$rate['meta_data']['no_count'] = (int) ceil($total / $this->weight_plus_cost);
				$rate['cost'] *= $rate['meta_data']['no_count'];
			}
		}

		$this->add_rate($rate);
		do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
	}

	public function save_order_update($order_id) {
		if( $order = wc_get_order($order_id) ) {
			foreach( $order->get_items('shipping') as $item_id => $item ) {
				$shipping_method = RY_ECPay_Shipping::get_order_support_shipping($item);
				if( $shipping_method == $this->id ) {
					if( isset($_POST['_shipping_phone']) ) {
						$order->update_meta_data('_shipping_cvs_store_ID', $_POST['_shipping_cvs_store_ID']);
						$order->update_meta_data('_shipping_cvs_store_name', $_POST['_shipping_cvs_store_name']);
						$order->update_meta_data('_shipping_cvs_store_address', $_POST['_shipping_cvs_store_address']);
						$order->update_meta_data('_shipping_cvs_store_telephone', $_POST['_shipping_cvs_store_telephone']);
						$order->update_meta_data('_shipping_phone', $_POST['_shipping_phone']);
						$order->save_meta_data();
					}
				}
			}
		}
	}
}
