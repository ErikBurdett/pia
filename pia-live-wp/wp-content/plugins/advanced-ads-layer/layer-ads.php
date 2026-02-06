<?php
/**
 * Advanced Ads – PopUp and Layer Ads
 *
 * @package   AdvancedAds
 * @author    Advanced Ads <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Advanced Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads – PopUp and Layer Ads
 * Version:           2.0.2
 * Description:       Create PopUp, Layer ads and Overlays
 * Plugin URI:        https://wpadvancedads.com/add-ons/popup-and-layer-ads/
 * Author:            Advanced Ads
 * Author URI:        https://wpadvancedads.com
 * Text Domain:       advanced-ads-layer
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @requires
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Requires Plugins:  advanced-ads
 */

// Early bail!!
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( defined( 'AAPLDS_FILE' ) ) {
	return;
}

define( 'AAPLDS_FILE', __FILE__ );
define( 'AAPLDS_VERSION', '2.0.2' );

// Load the autoloader.
require_once __DIR__ . '/includes/class-autoloader.php';
\AdvancedAds\Layer\Autoloader::get()->initialize();

if ( ! function_exists( 'wp_advads_layer' ) ) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since 1.8.0
	 *
	 * @return \AdvancedAds\Layer\Plugin
	 */
	function wp_advads_layer() {
		return \AdvancedAds\Layer\Plugin::get();
	}
}

\AdvancedAds\Layer\Bootstrap::get()->start();

