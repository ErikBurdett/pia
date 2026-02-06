<?php
/**
 * The plugin bootstrap.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds;

use Advanced_Ads;
use AdvancedAds\Framework\Loader;

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
		return AA_STICKY_ADS_VERSION;
	}

	/**
	 * Bootstrap plugin.
	 *
	 * @return void
	 */
	private function setup(): void {
		$this->define_constants();
		$this->includes();

		if ( is_admin() ) {
			$this->includes_admin();
		} else {
			$this->includes_frontend();
		}

		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_filter( 'advanced-ads-activate-advanced-js', '__return_true' );
		$this->load();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'advanced-ads-sticky' );

		unload_textdomain( 'advanced-ads-sticky' );
		if ( false === load_textdomain( 'advanced-ads-sticky', WP_LANG_DIR . '/plugins/advanced-ads-sticky-' . $locale . '.mo' ) ) {
			load_textdomain( 'advanced-ads-sticky', WP_LANG_DIR . '/advanced-ads-sticky/advanced-ads-sticky-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'advanced-ads-sticky', false, dirname( AA_STICKY_ADS_BASENAME ) . '/languages' );
	}

	/**
	 * Returns the slug for the sticky ads plugin.
	 *
	 * @return string The slug for the sticky ads plugin.
	 */
	public function get_slug(): string {
		return ADVADS_SLUG . '-sticky';
	}

	/**
	 * Get advanced ads settings
	 *
	 * @return array
	 */
	public function get_options() {
		return Advanced_Ads::get_instance()->options();
	}

	/**
	 * Check if a specific placement belongs to Advanced Ads Sticky
	 *
	 * @since 1.4.7
	 *
	 * @param string $placement_type Placement type.
	 *
	 * @return bool true, if placement belongs to this add-on
	 */
	public function is_sticky_placement( $placement_type = '' ) {
		if ( ! $placement_type ) {
			return false;
		}

		$sticky_placements = [
			'sticky_left_sidebar',
			'sticky_right_sidebar',
			'sticky_header',
			'sticky_footer',
			'sticky_left_window',
			'sticky_right_window',
		];

		return in_array( $placement_type, $sticky_placements, true );
	}

	/**
	 * Retrieves the sticky class.
	 *
	 * @return string The sticky class.
	 */
	public function get_sticky_class(): string {
		return wp_advads()->get_frontend_prefix() . 'sticky';
	}

	/**
	 * Define Advanced Ads constant
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'AA_STICKY_ADS_ABSPATH', dirname( AA_STICKY_ADS_FILE ) . '/' );
		$this->define( 'AA_STICKY_ADS_BASENAME', plugin_basename( AA_STICKY_ADS_FILE ) );
		$this->define( 'AA_STICKY_ADS_BASE_URL', plugin_dir_url( AA_STICKY_ADS_FILE ) );
		$this->define( 'AA_STICKY_ADS_SLUG', 'advanced-ads-sticky-ads' );

		// Deprecated Constants.
		/**
		 * AASADS_BASE_PATH
		 *
		 * @deprecated 1.9.0 use AA_STICKY_ADS_ABSPATH now.
		 */
		define( 'AASADS_BASE_PATH', AA_STICKY_ADS_ABSPATH );

		/**
		 * AASADS_BASE_DIR
		 *
		 * @deprecated 1.9.0 use AA_STICKY_ADS_BASENAME now.
		 */
		define( 'AASADS_BASE_DIR', AA_STICKY_ADS_BASENAME );

		/**
		 * AASADS_BASE_URL
		 *
		 * @deprecated 1.9.0 use AA_STICKY_ADS_BASE_URL now.
		 */
		define( 'AASADS_BASE_URL', AA_STICKY_ADS_BASE_URL );

		/**
		 * AASADS_SLUG
		 *
		 * @deprecated 1.9.0 use AA_STICKY_ADS_SLUG now.
		 */
		define( 'AASADS_SLUG', AA_STICKY_ADS_SLUG );

		/**
		 * AASADS_VERSION
		 *
		 * @deprecated 1.9.0 use AA_STICKY_ADS_VERSION now.
		 */
		define( 'AASADS_VERSION', AA_STICKY_ADS_VERSION );
	}

	/**
	 * Includes core files used in admin and on the frontend.
	 *
	 * @return void
	 */
	private function includes(): void {
		$this->register_integration( Placement_Types::class );
		$this->register_integration( Wrappers::class );
	}

	/**
	 * Includes core files used in frontend.
	 *
	 * @return void
	 */
	private function includes_frontend(): void {
		$this->register_integration( Frontend::class );
	}

	/**
	 * Includes core files used in admin.
	 *
	 * @return void
	 */
	private function includes_admin(): void {
		$this->register_integration( Admin\Metabox::class );
		$this->register_integration( Admin\Settings::class );
		$this->register_integration( Admin\Placement_Settings::class );
	}
}
