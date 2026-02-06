<?php
/**
 * Advanced Ads Slider
 *
 * @package   AdvancedAds
 * @author    Advanced Ads <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Advanced Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads – Slider
 * Version:           2.0.2
 * Description:       Create a slider from your ads.
 * Plugin URI:        https://wpadvancedads.com/add-ons/slider/
 * Author:            Advanced Ads
 * Author URI:        https://wpadvancedads.com
 * Text Domain:       slider-ads
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

if ( defined( 'AAS_FILE' ) ) {
	return;
}

define( 'AAS_FILE', __FILE__ );
define( 'AAS_VERSION', '2.0.2' );

// Load the autoloader.
require_once __DIR__ . '/includes/class-autoloader.php';
\AdvancedAds\Slider\Autoloader::get()->initialize();

if ( ! function_exists( 'wp_advads_slider' ) ) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since 1.5.0
	 *
	 * @return \AdvancedAds\Slider\Plugin
	 */
	function wp_advads_slider() {
		return \AdvancedAds\Slider\Plugin::get();
	}
}

\AdvancedAds\Slider\Bootstrap::get()->start();
