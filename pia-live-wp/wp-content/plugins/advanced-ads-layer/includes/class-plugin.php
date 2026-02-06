<?php
/**
 * The plugin bootstrap.
 *
 * @package AdvancedAds\Layer
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.8.0
 */

namespace AdvancedAds\Layer;

use Advanced_Ads_Layer;
use Advanced_Ads_Layer_Admin;
use AdvancedAds\Framework\Loader;
use AdvancedAds\Layer\Placements\Placement_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin.
 */
class Plugin extends Loader {

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Plugin
	 */
	public static function get(): Plugin {
		static $instance;

		if ( null === $instance ) {
			$instance = new Plugin();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return AAPLDS_VERSION;
	}

	/**
	 * Bootstrap plugin.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->define_constants();
		$this->includes();
		$this->includes_admin();

		/**
		 * Old loading strategy
		 *
		 * TODO: need to remove it in future.
		 */
		require_once plugin_dir_path( AAPLDS_FILE ) . 'classes/plugin.php';
		require_once plugin_dir_path( AAPLDS_FILE ) . 'public/public.php';
		( new Advanced_Ads_Layer() );

		// Dashboard and Administrative Functionality.
		if ( is_admin() && ! wp_doing_ajax() ) {
			require_once plugin_dir_path( AAPLDS_FILE ) . 'admin/admin.php';
			( new Advanced_Ads_Layer_Admin() );
		}

		add_action( 'init', [ $this, 'load_textdomain' ] );
		$this->load();
	}

	/**
	 * Define Advanced Ads constant
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'AA_LAYER_ADS_ABSPATH', dirname( AAPLDS_FILE ) . '/' );
		$this->define( 'AA_LAYER_ADS_BASENAME', plugin_basename( AAPLDS_FILE ) );
		$this->define( 'AA_LAYER_ADS_BASE_URL', plugin_dir_url( AAPLDS_FILE ) );
		$this->define( 'AA_LAYER_ADS_SLUG', 'advanced-ads-layer' );

		// Deprecated Constants.
		/**
		 * AAPLDS_BASE_PATH
		 *
		 * @deprecated 1.8.0 use AA_LAYER_ADS_ABSPATH now.
		 */
		define( 'AAPLDS_BASE_PATH', AA_LAYER_ADS_ABSPATH );

		/**
		 * AAPLDS_BASE_URL
		 *
		 * @deprecated 1.8.0 use AA_LAYER_ADS_BASE_URL now.
		 */
		define( 'AAPLDS_BASE_URL', AA_LAYER_ADS_BASE_URL );

		/**
		 * AAPLDS_SLUG
		 *
		 * @deprecated 1.8.0 use AA_LAYER_ADS_SLUG now.
		 */
		define( 'AAPLDS_SLUG', AA_LAYER_ADS_SLUG );
	}

	/**
	 * Includes core files used in admin and on the frontend.
	 *
	 * @return void
	 */
	private function includes(): void {
		$this->register_integration( Placement_Types::class );
	}

	/**
	 * Includes core files used in admin.
	 *
	 * @return void
	 */
	private function includes_admin(): void {
		if ( ! is_admin() ) {
			return;
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'advanced-ads-layer' );

		unload_textdomain( 'advanced-ads-layer' );
		if ( false === load_textdomain( 'advanced-ads-layer', WP_LANG_DIR . '/plugins/advanced-ads-layer-' . $locale . '.mo' ) ) {
			load_textdomain( 'advanced-ads-layer', WP_LANG_DIR . '/advanced-ads-layer/advanced-ads-layer-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'advanced-ads-layer', false, dirname( AA_LAYER_ADS_BASENAME ) . '/languages' );
	}
}
