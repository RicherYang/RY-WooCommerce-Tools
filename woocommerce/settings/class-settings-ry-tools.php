<?php
defined('RY_WT_VERSION') OR exit('No direct script access allowed');

if( !class_exists('WC_Settings_RY_Tools', false) ) {

	class WC_Settings_RY_Tools extends WC_Settings_Page {
		public function __construct() {
			$this->id    = 'rytools';
			$this->label = __('RY Tools', RY_WT::$textdomain);

			parent::__construct();
		}

		public function get_sections() {
			$sections = array(
				'' => __('Base options', RY_WT::$textdomain)
			);

			return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
		}

		public function output() {
			global $current_section;

			$settings = $this->get_settings($current_section);
			WC_Admin_Settings::output_fields($settings);
		}

		public function save() {
			global $current_section;

			$settings = $this->get_settings($current_section);
			WC_Admin_Settings::save_fields($settings);
		
			if( $current_section ) {
				do_action('woocommerce_update_options_' . $this->id . '_' . $current_section);
			}
		}

		public function get_settings($current_section = '') {
			$settings = array();
			if( $current_section == '' ) {
				$settings = array(
					array(
						'title' => __('ECPay support', RY_WT::$textdomain),
						'type'  => 'title',
						'id'    => 'ecpay_support',
					),
					array(
						'title'   => __('Gateway method', RY_WT::$textdomain),
						'desc'    => __('Enable ECPay gateway method', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'enabled_ecpay_gateway',
						'type'    => 'checkbox',
						'default' => 'yes',
					),
					array(
						'title'   => __('Shipping method', RY_WT::$textdomain),
						'desc'    => __('Enable ECPay shipping method', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'enabled_ecpay_shipping',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					array(
						'type' => 'sectionend',
						'id' => 'ecpay_support',
					),

					array(
						'title' => __('General options', RY_WT::$textdomain),
						'type'  => 'title',
						'id'    => 'general_options',
					),
					array(
						'title'   => __('Repay action', RY_WT::$textdomain),
						'desc'    => __('Edable order to change payment', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'repay_action',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					array(
						'title'   => __('strength password', RY_WT::$textdomain),
						'desc'    => __('Edable the strength password check', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'strength_password',
						'type'    => 'checkbox',
						'default' => 'yes',
					),
					array(
						'type' => 'sectionend',
						'id' => 'general_options',
					),

					array(
						'title' => __('Address options', RY_WT::$textdomain),
						'type'  => 'title',
						'id'    => 'checkout_page_options',
					),
					array(
						'title'   => __('Show Country', RY_WT::$textdomain),
						'desc'    => __('Show Country select item', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'show_country_select',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					array(
						'title'   => __('Last name first', RY_WT::$textdomain),
						'desc'    => __('Show Last name before first name input item', RY_WT::$textdomain),
						'id'      => RY_WT::$option_prefix . 'last_name_first',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					array(
						'type' => 'sectionend',
						'id' => 'checkout_page_options',
					)
				);
			}

			return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
		}
	}
}

return new WC_Settings_RY_Tools();
