<?php
/**
 * Update routine
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.6
 */

use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Installation\Install;

/**
 * Hotfix for missing stats on new year
 *
 * @since 1.6
 *
 * @param string $table Table name.
 *
 * @return void
 */
function fix_missing_stats( $table ): void {
	global $wpdb;

	$rows = $wpdb->get_results( "SELECT * FROM $table WHERE `timestamp` BETWEEN 1601530100 AND 1601530323" );
	if ( ! empty( $rows ) ) {
		foreach ( $rows as $row ) {
			$ts = str_replace( '53', '01', $row->timestamp );
			$wpdb->query( "UPDATE $table SET `timestamp` = $ts WHERE `timestamp` = $row->timestamp AND `ad_id` = $row->ad_id" );
		}
	}
}

/**
 * Update database schema
 *
 * @since 1.6
 *
 * @param string $version Plugin installed database version.
 *
 * @return void
 */
function database_schema_upgrade( $version ): void {
	global $wpdb;

	$sql               = [];
	$impressions_table = Database::get_impression_table();
	$clicks_table      = Database::get_click_table();

	switch ( wp_advads_tracking()->upgrades->get_installed_version() ) {
		case '1.1.0':
		case '1.2.0':
		case '1.3.0':
			// Update INT(10) to BIGINT(20) since this is the max size for WordPress post IDs.
			$sql[] = "ALTER TABLE $clicks_table CHANGE `ad_id` `ad_id` BIGINT(20) UNSIGNED NOT NULL";
			$sql[] = "ALTER TABLE $impressions_table CHANGE `ad_id` `ad_id` BIGINT(20) UNSIGNED NOT NULL";
		case '1.4.0':
			// Change timestamp column type on the clicks and impressions table to BIGINT.
			$sql[] = "ALTER TABLE $clicks_table MODIFY COLUMN `timestamp` BIGINT UNSIGNED NOT NULL";
			$sql[] = "ALTER TABLE $impressions_table MODIFY COLUMN `timestamp` BIGINT UNSIGNED NOT NULL";
	}

	foreach ( $sql as $query ) {
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

/**
 * Update timestamps in the database for a bug in the timestamp generation
 * for the first week in January if it was still calendar week 52.
 *
 * @return void
 */
function update_timestamps_for_week_52_in_january() {
	global $wpdb;

	foreach ( [ Database::get_impression_table(), Database::get_click_table() ] as $table ) {
		// phpcs:disable WordPress.DB.PreparedSQL
		$results = $wpdb->get_results( "SELECT * FROM {$table} WHERE LENGTH(`timestamp`) = 9", ARRAY_A );
		foreach ( $results as $row ) {
			if ( ! preg_match( '/(?<y>\d{2})(?<m>\d{2})(?<W>\d{1})(?<d>\d{2})(?<H>\d{2})/', $row['timestamp'], $timestamp_exploded ) ) {
				continue;
			}
			$timestamp_exploded = array_filter( $timestamp_exploded, 'is_string', ARRAY_FILTER_USE_KEY );
			if ( '1' !== $timestamp_exploded['W'] ) {
				continue;
			}

			unset( $row['count'] );
			$timestamp_exploded['W'] = '01';
			$updated                 = $row;
			$updated['timestamp']    = implode( '', $timestamp_exploded );

			if ( ! $wpdb->update( $table, $updated, $row ) && $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE timestamp = %d && ad_id = %d", $updated['timestamp'], $updated['ad_id'] ) ) !== null ) {
				$wpdb->delete( $table, $row );
			}
			// phpcs:enable WordPress.DB.PreparedSQL
		}
	}
}

/**
 * Update something
 *
 * @since 1.6
 *
 * @return void
 */
function advads_upgrade_1_5_create_tables(): void {
	$installer = new Install();
	$installer->create_tables();

	$installed_version = wp_advads_tracking()->upgrades->get_installed_version();

	fix_missing_stats( Database::get_impression_table() );
	fix_missing_stats( Database::get_click_table() );
	database_schema_upgrade( $installed_version );

	if ( version_compare( $installed_version, '1.7', '<' ) ) {
		update_timestamps_for_week_52_in_january();
	}
}

advads_upgrade_1_5_create_tables();
