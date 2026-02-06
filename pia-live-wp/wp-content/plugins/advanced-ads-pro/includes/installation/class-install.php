<?php
/**
 * The class provides plugin installation routines.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Installation;

use Advanced_Ads_Pro;

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
		if ( null !== AAP_FILE ) {
			register_activation_hook( AAP_FILE, [ $this, 'activation' ] );
			register_deactivation_hook( AAP_FILE, [ $this, 'deactivation' ] );

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
		register_uninstall_hook( AAP_FILE, [ static::class, 'uninstall' ] );

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
		global $wpdb;

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
		add_role(
			'advanced_ads_admin',
			__( 'Ad Admin', 'advanced-ads-pro' ),
			[
				'read'                           => true,
				'advanced_ads_manage_options'    => true,
				'advanced_ads_see_interface'     => true,
				'advanced_ads_edit_ads'          => true,
				'advanced_ads_manage_placements' => true,
				'advanced_ads_place_ads'         => true,
				'upload_files'                   => true,
				'unfiltered_html'                => true,
			]
		);
		add_role(
			'advanced_ads_manager',
			__( 'Ad Manager', 'advanced-ads-pro' ),
			[
				'read'                           => true,
				'advanced_ads_see_interface'     => true,
				'advanced_ads_edit_ads'          => true,
				'advanced_ads_manage_placements' => true,
				'advanced_ads_place_ads'         => true,
				'upload_files'                   => true,
				'unfiltered_html'                => true,
			]
		);
		add_role(
			'advanced_ads_user',
			__( 'Ad User', 'advanced-ads-pro' ),
			[
				'read'                   => true,
				'advanced_ads_place_ads' => true,
			]
		);

		Advanced_Ads_Pro::get_instance()
			->enable_placement_test_emails();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	protected function deactivate(): void {
		remove_role( 'advanced_ads_admin' );
		remove_role( 'advanced_ads_manager' );
		remove_role( 'advanced_ads_user' );

		Advanced_Ads_Pro::get_instance()
			->disable_placement_test_emails();
	}

	/**
	 * Plugin uninstall callback.
	 *
	 * @return void
	 */
	public static function uninstall(): void {}
}
