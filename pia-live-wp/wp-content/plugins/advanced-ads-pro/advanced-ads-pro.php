<?php
/**
 * Advanced Ads Pro
 *
 * @package   AdvancedAds
 * @author    Advanced Ads <support@wpadvancedads.com>
 * @license   GPL-2.0+
 * @link      https://wpadvancedads.com
 * @copyright since 2013 Advanced Ads
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Ads Pro
 * Version:           3.0.8
 * Description:       Advanced features to boost your ad revenue.
 * Plugin URI:        https://wpadvancedads.com/add-ons/advanced-ads-pro/
 * Author:            Advanced Ads
 * Author URI:        https://wpadvancedads.com
 * Text Domain:       advanced-ads-pro
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

if ( defined( 'AAP_FILE' ) ) {
	return;
}

define( 'AAP_FILE', __FILE__ );
define( 'AAP_VERSION', '3.0.8' );

// Load the autoloader.
require_once __DIR__ . '/includes/class-autoloader.php';
\AdvancedAds\Pro\Autoloader::get()->initialize();

/**
 * Install the plugin.
 *
 * @since 2.26.0
 */
( new \AdvancedAds\Pro\Installation\Install() )->initialize();

if ( ! function_exists( 'wp_advads_pro' ) ) {
	/**
	 * Returns the main instance of the plugin.
	 *
	 * @since 2.26.0
	 *
	 * @return \AdvancedAds\Pro\Plugin
	 */
	function wp_advads_pro() {
		return \AdvancedAds\Pro\Plugin::get();
	}
}

\AdvancedAds\Pro\Bootstrap::get()->start();

