<?php
/**
 * Advanced Ads – Tracking
 *
 * @package   AdvancedAds\Tracking
 * @author    Advanced Ads <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Advanced Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads – Tracking
 * Version:           3.0.8
 * Description:       Track ad impressions and clicks.
 * Plugin URI:        https://wpadvancedads.com/add-ons/tracking/
 * Author:            Advanced Ads
 * Author URI:        https://wpadvancedads.com
 * Text Domain:       advanced-ads-tracking
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @requires
 * Requires at least: 5.7
 * Requires PHP:      7.4
 */

// Early bail!!
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( defined( 'AAT_FILE' ) ) {
	return;
}

define( 'AAT_FILE', __FILE__ );
define( 'AAT_VERSION', '3.0.8' );

// Load the autoloader.
require_once __DIR__ . '/includes/class-autoloader.php';
\AdvancedAds\Tracking\Autoloader::get()->initialize();

/**
 * Install the plugin.
 *
 * @since 2.6.0
 */
( new \AdvancedAds\Tracking\Installation\Install() )->initialize();

if ( ! function_exists( 'wp_advads_tracking' ) ) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since 2.6.0
	 *
	 * @return \AdvancedAds\Tracking\Plugin
	 */
	function wp_advads_tracking() {
		return \AdvancedAds\Tracking\Plugin::get();
	}
}

\AdvancedAds\Tracking\Bootstrap::get()->start();

