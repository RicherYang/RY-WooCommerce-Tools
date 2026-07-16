<?php

/**
 * Plugin Name: RY Tools for WooCommerce
 * Plugin URI: https://ry-plugin.com/ry-woocommerce-tools
 * Description: WooCommerce payment and shipping tools
 * Version: 2026.7.16
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv3
 *
 * Text Domain: ry-woocommerce-tools
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

define('RY_WT_VERSION', '2026.7.16');
define('RY_WT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once RY_WT_PLUGIN_DIR . 'includes/vendor/autoload.php';
require_once RY_WT_PLUGIN_DIR . 'includes/main.php';

register_activation_hook(__FILE__, ['RY_WT', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WT', 'plugin_deactivation']);

function RY_WT(): RY_WT
{
    return RY_WT::instance();
}

RY_WT();
