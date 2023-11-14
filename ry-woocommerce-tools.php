<?php
/**
 * Plugin Name: RY WooCommerce Tools
 * Plugin URI: https://ry-plugin.com/ry-woocommerce-tools
 * Description: WooCommerce paymet and shipping tools
 * Version: 3.0.3
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Text Domain: ry-woocommerce-tools
 * Domain Path: /languages
 *
 * WC requires at least: 7
 */

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WT_VERSION', '3.0.3');
define('RY_WT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RY_WT_PLUGIN_LANGUAGES_DIR', plugin_dir_path(__FILE__) . '/languages');

require_once RY_WT_PLUGIN_DIR . 'includes/main.php';

register_activation_hook(__FILE__, ['RY_WT', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WT', 'plugin_deactivation']);

function RY_WT(): RY_WT
{
    return RY_WT::instance();
}

RY_WT();
