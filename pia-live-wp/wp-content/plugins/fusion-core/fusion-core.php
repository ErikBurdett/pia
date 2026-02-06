<?php
/**
 * Plugin Name: Avada Core
 * Description: The Avada Website Builder is the ultimate design and creation suite. Design Anything, Build Everything, Fast. The #1 selling product of all time on ThemeForest.
 * Plugin URI: https://avada.com
 * Author: ThemeFusion
 * Author URI: https://themeforest.net/user/ThemeFusion
 * License: Themeforest Split Licence
 * Version: 5.14
 * Requires PHP: 7.2
 * Requires at least: 5.7
 * Text Domain: fusion-core
 *
 * @package Avada-Core
 * @subpackage Core
 */

// Plugin version.
if ( ! defined( 'FUSION_CORE_VERSION' ) ) {
	define( 'FUSION_CORE_VERSION', '5.14' );
}

// Minimum PHP version required.
if ( ! defined( 'FUSION_CORE_MIN_PHP_VER_REQUIRED' ) ) {
	define( 'FUSION_CORE_MIN_PHP_VER_REQUIRED', '7.2' );
}

// Minimum WP version required.
if ( ! defined( 'FUSION_CORE_MIN_WP_VER_REQUIRED' ) ) {
	define( 'FUSION_CORE_MIN_WP_VER_REQUIRED', '5.7' );
}

// Plugin Folder Path.
if ( ! defined( 'FUSION_CORE_PATH' ) ) {
	define( 'FUSION_CORE_PATH', wp_normalize_path( __DIR__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'FUSION_CORE_URL' ) ) {
	define( 'FUSION_CORE_URL', plugin_dir_url( __FILE__ ) );
}

// The main plugin file path.
if ( ! defined( 'FUSION_CORE_MAIN_PLUGIN_FILE' ) ) {
	define( 'FUSION_CORE_MAIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Compatibility check.
 *
 * Check that the site meets the minimum requirements for the plugin before proceeding.
 *
 * @since 4.0
 */
if ( version_compare( $GLOBALS['wp_version'], FUSION_CORE_MIN_WP_VER_REQUIRED, '<' ) || version_compare( PHP_VERSION, FUSION_CORE_MIN_PHP_VER_REQUIRED, '<' ) ) {
	require_once FUSION_CORE_PATH . '/includes/bootstrap-compat.php';
	return;
}

/**
 * Bootstrap the plugin.
 *
 * @since 4.0
 */
require_once FUSION_CORE_PATH . '/includes/bootstrap.php';

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
