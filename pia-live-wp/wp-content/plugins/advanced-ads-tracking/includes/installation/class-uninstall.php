<?php
/**
 * The class provides plugin uninstallation routines.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Installation;

use Advanced_Ads_Admin_Licenses;
use AdvancedAds\Tracking\Debugger;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Framework\Interfaces\Initializer_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Uninstall.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
class Uninstall implements Initializer_Interface {

	/**
	 * Runs this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->license();
		( new Tracking_Installer() )->uninstall(); // Remove AJAX dropin.

		if ( ! is_multisite() ) {
			$this->uninstall();
			return;
		}

		$site_ids = Install::get_sites();

		if ( empty( $site_ids ) ) {
			return;
		}

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$this->uninstall();
			restore_current_blog();
		}
	}

	/**
	 * Deactivate license
	 *
	 * @return void
	 */
	private function license(): void {
		// Early bail!!
		if ( ! class_exists( 'Advanced_Ads_Admin_Licenses' ) ) {
			return;
		}

		$options_key = 'advanced-ads-tracking';
		$manager     = Advanced_Ads_Admin_Licenses::get_instance();

		if ( 'valid' === $manager->get_license_status( $options_key ) ) {
			$manager->deactivate_license( 'tracking', 'Tracking', $options_key );
		}
	}

	/**
	 * Fired for each blog when the plugin is uninstalled.
	 *
	 * @return void
	 */
	private function uninstall(): void {
		$options = get_option( Constants::OPTIONS_SLUG );

		if ( isset( $options['uninstall'] ) && $options['uninstall'] ) {
			$this->drop_tables();
			$this->delete_options();
			$this->delete_usermeta();

			wp_cache_flush();
		}
	}

	/**
	 * Drop tables
	 *
	 * @return void
	 */
	private function drop_tables(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'DROP TABLE IF EXISTS %s, %s;',
				$wpdb->prefix . 'advads_impressions',
				$wpdb->prefix . 'advads_clicks'
			)
		);
	}

	/**
	 * Delete options.
	 *
	 * @return void
	 */
	private function delete_options(): void {
		delete_option( 'advanced-ads-tracking' );
		delete_option( Debugger::DEBUG_FILENAME_OPT );
		delete_option( Debugger::DEBUG_OPT );
	}

	/**
	 * Delete usermeta.
	 *
	 * @return void
	 */
	private function delete_usermeta(): void {
		delete_metadata( 'user', null, 'advads_tracking_performing_ads_prefs', '', true );
	}
}
