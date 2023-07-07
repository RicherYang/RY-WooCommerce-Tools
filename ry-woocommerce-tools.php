<?php
/**
 * Plugin Name: RY WooCommerce Tools
 * Plugin URI: https://ry-plugin.com/ry-woocommerce-tools
 * Description: WooCommerce Tools
 * Version: 2.0.4
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Text Domain: ry-woocommerce-tools
 * Domain Path: /languages
 *
 * WC requires at least: 6
 */

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WT_VERSION', '2.0.4');
define('RY_WT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once RY_WT_PLUGIN_DIR . 'class.ry-wt.main.php';

register_activation_hook(__FILE__, ['RY_WT', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WT', 'plugin_deactivation']);

add_action('init', ['RY_WT', 'init'], 10);
