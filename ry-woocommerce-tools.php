<?php
/*
Plugin Name: RY WooCommerce Tools
Plugin URI: https://richer.tw/ry-woocommerce-tools
Description: WooCommerce Tools
Version: 1.0.4
Author: Richer Yang
Author URI: https://richer.tw/
Text Domain: ry-woocommerce-tools
Domain Path: /languages
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.txt
WC tested up to: 3.5.3
*/

function_exists('plugin_dir_url') OR exit('No direct script access allowed');

define('RY_WT_VERSION', '1.0.4');
define('RY_WT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once(RY_WT_PLUGIN_DIR . 'class.ry-wt.main.php');

register_activation_hook( __FILE__, array('RY_WT', 'plugin_activation'));
register_deactivation_hook( __FILE__, array('RY_WT', 'plugin_deactivation'));

add_action('init', array('RY_WT', 'init'));
