<?php
/**
 * The plugin bootstrap.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use AdvancedAds\Tracking\Crons;
use AdvancedAds\Tracking\Admin;
use AdvancedAds\Framework\Loader;
use AdvancedAds\Tracking\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin.
 *
 * @property Options         $options  Options.
 * @property Assets_Registry $registry Assets registry.
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
		return AAT_VERSION;
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
		// register AJAX actions for Database Operations.
		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			Db_Operations::get_instance();
		}

		Ad_Limiter::register_event_hooks();
		Debugger::check_debugging_constant();
		Debugger::delete_expired_option();

		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'advanced-ads-ad-expired', [ Ad_Limiter::class, 'remove_events_for_ad' ] );
		$this->load();
	}

	/**
	 * Define Advanced Ads constant
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'AA_TRACKING_ABSPATH', dirname( AAT_FILE ) . '/' );
		$this->define( 'AA_TRACKING_BASENAME', plugin_basename( AAT_FILE ) );
		$this->define( 'AA_TRACKING_BASE_URL', plugin_dir_url( AAT_FILE ) );
		$this->define( 'AA_TRACKING_SLUG', 'advads-tracking' );

		// Deprecated Constants.
		/**
		 * AAT_BASE
		 *
		 * @deprecated 2.6.0 use AA_TRACKING_BASENAME now.
		 */
		define( 'AAT_BASE', AA_TRACKING_BASENAME );

		/**
		 * AAT_BASE_PATH
		 *
		 * @deprecated 2.6.0 use AA_TRACKING_ABSPATH now.
		 */
		define( 'AAT_BASE_PATH', AA_TRACKING_ABSPATH );

		/**
		 * AAT_BASE_URL
		 *
		 * @deprecated 2.6.0 use AA_TRACKING_BASE_URL now.
		 */
		define( 'AAT_BASE_URL', AA_TRACKING_BASE_URL );

		/**
		 * AAT_BASE_DIR
		 *
		 * @deprecated 2.6.0 Avoid global declaration of the constant used exclusively in `load_text_domain` function; use localized declaration instead.
		 */
		define( 'AAT_BASE_DIR', dirname( AA_TRACKING_BASENAME ) );

		/**
		 * AAT_SLUG
		 *
		 * @deprecated 2.6.0 use AA_TRACKING_SLUG now.
		 */
		define( 'AAT_SLUG', AA_TRACKING_SLUG );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'advanced-ads-tracking' );

		unload_textdomain( 'advanced-ads-tracking' );
		if ( false === load_textdomain( 'advanced-ads-tracking', WP_LANG_DIR . '/plugins/advanced-ads-tracking-' . $locale . '.mo' ) ) {
			load_textdomain( 'advanced-ads-tracking', WP_LANG_DIR . '/advanced-ads-tracking/advanced-ads-tracking-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'advanced-ads-tracking', false, dirname( AA_TRACKING_BASENAME ) . '/languages' );
	}

	/**
	 * Includes core files used in admin and on the frontend.
	 *
	 * @return void
	 */
	private function includes(): void {
		require_once AA_TRACKING_ABSPATH . 'includes/functions.php';

		$this->register_initializer( Options::class, 'options' );
		$this->register_initializer( Crons\Reporting_Emails::class );
		$this->register_integration( Assets_Registry::class, 'registry' );
		$this->register_integration( Shortcodes::class );
		$this->register_integration( Compatibility::class );
		$this->register_integration( Frontend\Frontend_Tracking::class );
		$this->register_integration( AMP::class );

		if ( wp_doing_ajax() ) {
			$this->register_integration( Ajax::class );
			add_action( 'advanced-ads-pre-reset-stats', [ Ad_Limiter::class, 'reset_stats' ] );
		}
		$this->register_integration( Frontend\Tracking::class );

		if ( ! defined( 'ADVANCED_ADS_TRACKING_NO_PUBLIC_STATS' ) ) {
			$this->register_initializer( Frontend\Public_Stats::class );
		}
	}

	/**
	 * Includes files used in admin.
	 *
	 * @return void
	 */
	private function includes_admin(): void {
		// Early bail!!
		if ( ! is_admin() ) {
			return;
		}

		$this->register_initializer( Upgrades::class, 'upgrades' );
		$this->register_integration( Admin\Admin::class );
		$this->register_integration( Admin\Ad_List_Table::class );
		$this->register_integration( Admin\WordPress_Dashboard::class );
		$this->register_integration( Admin\Quick_Bulk_Edit::class );
		$this->register_integration( Admin\Settings::class );
		$this->register_integration( Admin\Metaboxes::class );
		$this->register_integration( Crons\Jobs::class );
		$this->register_integration( Admin\Quick_Edit::class );
	}

	/**
	 * Includes files used on the frontend.
	 *
	 * @return void
	 */
	private function includes_frontend(): void {
		// Early bail!!
		if ( is_admin() ) {
			return;
		}
	}

	/**
	 * Load advanced ads settings.
	 * If options are empty or in old format, convert to new options.
	 *
	 * @deprecated 2.6.0 use `wp_advads_tracking()->options->get_all()` instead.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return $this->options->get_all();
	}

	/**
	 * Update plugin options
	 *
	 * @deprecated 2.6.0 use `wp_advads_tracking()->options->update()` instead.
	 *
	 * @param array $options Options array.
	 *
	 * @return void
	 */
	public function update_options( array $options ): void {
		$this->options->update( $options );
	}
}
