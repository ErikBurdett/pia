<?php
/**
 * Update routine
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.0
 */

use AdvancedAds\Tracking\Database;

/**
 * Fix corrupted data for 2018/12/31
 */
function advads_upgrade_1_0_create_tables() {
	global $wpdb;

	$impressions = Database::get_impression_table();
	$clicks      = Database::get_click_table();

	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.DB -- we can't prepare the table names.
	$result  = $wpdb->query( "UPDATE {$impressions} SET `timestamp` = 1812523106 WHERE `timestamp` = 1812013106" );
	$result2 = $wpdb->query( "UPDATE {$clicks} SET `timestamp` = 1812523106 WHERE `timestamp` = 1812013106" );
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.DB
}

advads_upgrade_1_0_create_tables();
