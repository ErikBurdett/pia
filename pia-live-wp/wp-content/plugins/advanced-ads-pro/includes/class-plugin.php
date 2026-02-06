<?php
/**
 * The plugin bootstrap.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro;

use Advanced_Ads_Pro;
use AdvancedAds\Pro\Admin;
use AdvancedAds\Framework;
use AdvancedAds\Pro\Placements\Placement_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin.
 *
 * Containers:
 *
 * @property Framework\Assets_Registry $registry Assets registry.
 */
class Plugin extends Framework\Loader {

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
		return AAP_VERSION;
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
		$this->includes_frontend();

		/**
		 * Old loading strategy
		 *
		 * TODO: need to remove it in future.
		 */
		Advanced_Ads_Pro::get_instance();

		add_action( 'init', [ $this, 'load_textdomain' ] );
		$this->load();
	}

	/**
	 * Define Advanced Ads constant
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'AA_PRO_ABSPATH', dirname( AAP_FILE ) . '/' );
		$this->define( 'AA_PRO_BASENAME', plugin_basename( AAP_FILE ) );
		$this->define( 'AA_PRO_BASE_URL', plugin_dir_url( AAP_FILE ) );
		$this->define( 'AA_PRO_SLUG', 'advanced-ads-pro' );

		// Deprecated Constants.
		/**
		 * AAP_PATH
		 *
		 * @deprecated 2.26.0 use AA_PRO_ABSPATH now.
		 */
		define( 'AAP_PATH', AA_PRO_ABSPATH );

		/**
		 * AAP_BASE_PATH
		 *
		 * @deprecated 2.26.0 use AA_PRO_ABSPATH now.
		 */
		define( 'AAP_BASE_PATH', AA_PRO_ABSPATH );

		/**
		 * AAP_BASE
		 *
		 * @deprecated 2.26.0 use AA_PRO_BASENAME now.
		 */
		define( 'AAP_BASE', AA_PRO_BASENAME );

		/**
		 * AAP_BASE_URL
		 *
		 * @deprecated 2.26.0 use AA_PRO_BASE_URL now.
		 */
		define( 'AAP_BASE_URL', AA_PRO_BASE_URL );

		/**
		 * AAP_SLUG
		 *
		 * @deprecated 2.26.0 use AA_PRO_SLUG now.
		 */
		define( 'AAP_SLUG', AA_PRO_SLUG );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'advanced-ads-pro' );

		unload_textdomain( 'advanced-ads-pro' );
		if ( false === load_textdomain( 'advanced-ads-pro', WP_LANG_DIR . '/plugins/advanced-ads-pro-' . $locale . '.mo' ) ) {
			load_textdomain( 'advanced-ads-pro', WP_LANG_DIR . '/advanced-ads-pro/advanced-ads-pro-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'advanced-ads-pro', false, dirname( AA_PRO_BASENAME ) . '/languages' );
	}

	/**
	 * Includes core files used in admin and on the frontend.
	 *
	 * @return void
	 */
	private function includes(): void {
		$this->register_integration( Adsense::class );
		$this->register_integration( Assets_Manager::class, 'registry' );
		$this->register_integration( Placement_Types::class );
		$this->register_integration( Shortcodes::class );
	}

	/**
	 * Includes the necessary files for the admin section of the plugin.
	 *
	 * @return void
	 */
	private function includes_admin(): void {
		// Early bail!!
		if ( ! is_admin() ) {
			return;
		}

		$this->register_integration( Admin\Admin::class );
		$this->register_initializer( Upgrades::class, 'upgrades' );
		$this->register_integration( Admin\Adsense::class );
		$this->register_integration( Admin\Ad_List_Table::class );
		$this->register_integration( Admin\Group_Duplication::class );
		$this->register_integration( Admin\Duplicate_Placement::class );
		$this->register_integration( Admin\Placements\Bulk_Edit::class );
		$this->register_integration( Admin\Settings::class );
	}

	/**
	 * Includes the necessary files for the frontend section of the plugin.
	 *
	 * @return void
	 */
	private function includes_frontend(): void {
		// Early bail!!
		if ( is_admin() ) {
			return;
		}

		$this->register_integration( Frontend\Scripts::class );
	}
}
