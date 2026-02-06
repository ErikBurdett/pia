<?php
/**
 * The plugin bootstrap.
 *
 * @package AdvancedAds\Slider
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.5.0
 */

namespace AdvancedAds\Slider;

use AdvancedAds\Framework\Loader;
use AdvancedAds\Slider\Admin\Admin;
use AdvancedAds\Slider\Frontend\Frontend;

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
		return AAS_VERSION;
	}

	/**
	 * Bootstrap plugin.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->define_constants();
		$this->includes();

		add_action( 'init', [ $this, 'load_textdomain' ] );
		$this->load();
	}

	/**
	 * Define Advanced Ads constant
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'AA_SLIDER_ABSPATH', dirname( AAS_FILE ) . '/' );
		$this->define( 'AA_SLIDER_BASENAME', plugin_basename( AAS_FILE ) );
		$this->define( 'AA_SLIDER_BASE_URL', plugin_dir_url( AAS_FILE ) );
		$this->define( 'AA_SLIDER_SLUG', 'slider-ads' );

		// Deprecated Constants.
		/**
		 * AAS_BASE_PATH
		 *
		 * @deprecated 1.5.0 use AA_SLIDER_ABSPATH now.
		 */
		define( 'AAS_BASE_PATH', AA_SLIDER_ABSPATH );

		/**
		 * AAS_BASE_URL
		 *
		 * @deprecated 1.5.0 use AA_SLIDER_BASE_URL now.
		 */
		define( 'AAS_BASE_URL', AA_SLIDER_BASE_URL );

		/**
		 * AAS_SLUG
		 *
		 * @deprecated 1.5.0 use AA_SLIDER_SLUG now.
		 */
		define( 'AAS_SLUG', AA_SLIDER_SLUG );
	}

	/**
	 * Includes core files used in admin and on the frontend.
	 *
	 * @return void
	 */
	private function includes(): void {
		$this->register_integration( Group_Types::class );
		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->register_integration( Admin::class );
		} else {
			$this->register_integration( Frontend::class );
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'slider-ads' );

		unload_textdomain( 'slider-ads' );
		if ( false === load_textdomain( 'slider-ads', WP_LANG_DIR . '/plugins/advanced-ads-slider-' . $locale . '.mo' ) ) {
			load_textdomain( 'slider-ads', WP_LANG_DIR . '/advanced-ads-slider/slider-ads-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'slider-ads', false, dirname( AA_SLIDER_BASENAME ) . '/languages' );
	}
}
