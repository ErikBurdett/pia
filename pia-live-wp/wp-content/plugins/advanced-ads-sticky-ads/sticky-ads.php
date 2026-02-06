<?php
/**
 * Advanced Ads – Sticky Ads
 *
 * @package   AdvancedAds
 * @author    Advanced Ads <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Advanced Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads – Sticky Ads
 * Version:           2.0.3
 * Description:       Advanced ad positioning.
 * Plugin URI:        http://wpadvancedads.com/add-ons/sticky-ads/
 * Author:            Advanced Ads
 * Author URI:        https://wpadvancedads.com
 * Text Domain:       advanced-ads-sticky
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

if ( defined( 'AA_STICKY_ADS_FILE' ) ) {
	return;
}

define( 'AA_STICKY_ADS_FILE', __FILE__ );
define( 'AA_STICKY_ADS_VERSION', '2.0.3' );

// Load the autoloader.
require_once __DIR__ . '/includes/class-autoloader.php';
\AdvancedAds\StickyAds\Autoloader::get()->initialize();

if ( ! function_exists( 'wp_advads_stickyads' ) ) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since 1.9.0
	 *
	 * @return \AdvancedAds\StickyAds\Plugin
	 */
	function wp_advads_stickyads() {
		return \AdvancedAds\StickyAds\Plugin::get();
	}
}

\AdvancedAds\StickyAds\Bootstrap::get()->start();

