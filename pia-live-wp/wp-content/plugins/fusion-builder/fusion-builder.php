<?php
/**
 * Plugin Name: Avada Builder
 * Description: The Avada Website Builder is the ultimate design and creation suite. Design Anything, Build Everything, Fast. The #1 selling product of all time on ThemeForest.
 * Plugin URI: https://avada.com
 * Author: ThemeFusion
 * Author URI: https://themeforest.net/user/ThemeFusion
 * License: Themeforest Split Licence
 * Version: 3.14
 * Requires PHP: 7.2
 * Requires at least: 5.7
 * Text Domain: fusion-builder
 *
 * @package fusion-builder
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Developer mode.
if ( ! defined( 'FUSION_BUILDER_DEV_MODE' ) ) {
	define( 'FUSION_BUILDER_DEV_MODE', false );
}

// Plugin version.
if ( ! defined( 'FUSION_BUILDER_VERSION' ) ) {
	define( 'FUSION_BUILDER_VERSION', '3.14' );
}

// Minimum PHP version required.
if ( ! defined( 'FUSION_BUILDER_MIN_PHP_VER_REQUIRED' ) ) {
	define( 'FUSION_BUILDER_MIN_PHP_VER_REQUIRED', '7.2' );
}

// Minimum WP version required.
if ( ! defined( 'FUSION_BUILDER_MIN_WP_VER_REQUIRED' ) ) {
	define( 'FUSION_BUILDER_MIN_WP_VER_REQUIRED', '5.7' );
}

// Plugin Folder Path.
if ( ! defined( 'FUSION_BUILDER_PLUGIN_DIR' ) ) {
	define( 'FUSION_BUILDER_PLUGIN_DIR', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
}

// Plugin Folder URL.
if ( ! defined( 'FUSION_BUILDER_PLUGIN_URL' ) ) {
	define( 'FUSION_BUILDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin Root File.
if ( ! defined( 'FUSION_BUILDER_PLUGIN_FILE' ) ) {
	define( 'FUSION_BUILDER_PLUGIN_FILE', wp_normalize_path( __FILE__ ) );
}

/**
 * Compatibility check.
 *
 * Check that the site meets the minimum requirements for the plugin before proceeding.
 *
 * @since 4.0
 */
if ( version_compare( $GLOBALS['wp_version'], FUSION_BUILDER_MIN_WP_VER_REQUIRED, '<' ) || version_compare( PHP_VERSION, FUSION_BUILDER_MIN_PHP_VER_REQUIRED, '<' ) ) {
	require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/bootstrap-compat.php';
	return;
}

/**
 * Bootstrap the plugin.
 *
 * @since 4.0
 */
require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/bootstrap.php';
