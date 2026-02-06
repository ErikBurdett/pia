<?php
/**
 * The class provides plugin installation routines.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Installation;

use AdvancedAds\Tracking\Constants;
use AdvancedAds\Tracking\Ad_Limiter;

defined( 'ABSPATH' ) || exit;

/**
 * Install.
 */
class Install {

	/**
	 * Runs this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {
		if ( null !== AAT_FILE ) {
			register_activation_hook( AAT_FILE, [ $this, 'activation' ] );
			register_deactivation_hook( AAT_FILE, [ $this, 'deactivation' ] );

			add_action( 'wp_initialize_site', [ $this, 'initialize_site' ] );
		}
	}

	/**
	 * Activation routine.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 *
	 * @return void
	 */
	public function activation( $network_wide = false ): void {
		register_uninstall_hook( AAT_FILE, [ static::class, 'uninstall' ] );

		if ( ! is_multisite() || ! $network_wide ) {
			$this->activate();
			return;
		}

		$this->network_activate_deactivate( 'activate' );
	}

	/**
	 * Deactivation routine.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 *
	 * @return void
	 */
	public function deactivation( $network_wide = false ): void {
		if ( ! is_multisite() || ! $network_wide ) {
			$this->deactivate();
			return;
		}

		$this->network_activate_deactivate( 'deactivate' );
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param WP_Site $site The new site's object.
	 *
	 * @return void
	 */
	public function initialize_site( $site ): void {
		switch_to_blog( $site->blog_id );
		$this->activate();
		restore_current_blog();
	}

	/**
	 * Run network-wide activation/deactivation of the plugin.
	 *
	 * @param string $action Action to perform.
	 *
	 * @return void
	 */
	private function network_activate_deactivate( $action ): void {
		// Early bail!!
		$site_ids = self::get_sites();
		if ( empty( $site_ids ) ) {
			return;
		}

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$this->$action();
			restore_current_blog();
		}
	}

	/**
	 * Get network sites
	 *
	 * @return array|int
	 */
	public static function get_sites() {
		global $wpdb;

		return get_sites(
			[
				'archived'   => 0,
				'spam'       => 0,
				'deleted'    => 0,
				'network_id' => $wpdb->siteid,
				'fields'     => 'ids',
			]
		);
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	protected function activate(): void {
		$this->create_tables();
		$ajax_handler_installer = new Tracking_Installer();
		$ajax_handler_installer::trigger_installer_update();
		$ajax_handler_installer->install();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	protected function deactivate(): void {
		wp_clear_scheduled_hook( 'advanced_ads_daily_email' );
		wp_clear_scheduled_hook( 'advanced_ads_auto_comp' );
		wp_clear_scheduled_hook( 'advanced_ads_daily_report' );
		delete_option( Constants::OPTIONS_DEBUG );
		( new Tracking_Installer() )->uninstall();
		Ad_Limiter::deactivate();
	}

	/**
	 * Plugin uninstall callback.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		( new Uninstall() )->initialize();
	}

	/**
	 * Create table on installation
	 *
	 * @since 1.0.0
	 * @link  http://codex.wordpress.org/Creating_Tables_with_Plugins
	 *
	 * @return void
	 */
	public function create_tables(): void {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Impression table.
		$table = "CREATE TABLE {$wpdb->prefix}advads_impressions (
			`timestamp` BIGINT UNSIGNED NOT NULL,
			`ad_id` BIGINT(20) UNSIGNED NOT NULL,
			`count` MEDIUMINT UNSIGNED NOT NULL,
			PRIMARY KEY (`timestamp`, `ad_id`)
		) $collate";
		maybe_create_table( $wpdb->prefix . 'advads_impressions', $table );

		// Click table.
		$table = "CREATE TABLE {$wpdb->prefix}advads_clicks (
			`timestamp` BIGINT UNSIGNED NOT NULL,
			`ad_id` BIGINT(20) UNSIGNED NOT NULL,
			`count` MEDIUMINT UNSIGNED NOT NULL,
			PRIMARY KEY (`timestamp`, `ad_id`)
		) $collate";
		maybe_create_table( $wpdb->prefix . 'advads_clicks', $table );
	}
}
