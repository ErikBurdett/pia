<?php
/**
 * The class hold database queries.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

/**
 * Constants.
 */
class Constants {

	/**
	 * The slug of the tracking options.
	 *
	 * @var string
	 */
	const OPTIONS_SLUG = 'advanced-ads-tracking';

	/**
	 * The slug of the tracking debug options.
	 *
	 * @var string
	 */
	const OPTIONS_DEBUG = 'advads_track_debug';

	/**
	 * Constant representing the name of the database table used for storing ad click data.
	 *
	 * @var string
	 */
	const TABLE_CLICKS = 'advads_clicks';

	/**
	 * Constant representing the name of the database table used for storing ad impression data.
	 *
	 * @var string
	 */
	const TABLE_IMPRESSIONS = 'advads_impressions';

	/**
	 * Default click link base
	 *
	 * @var string
	 */
	const DEFAULT_CLICK_LINKBASE = 'linkout';

	/**
	 * The default slug used for public ad stats page.
	 *
	 * @var string
	 */
	const DEFAULT_PUBLIC_STATS_SLUG = 'ad-stats';

	/**
	 * Constant representing a modified hour value.
	 *
	 * @var int MOD_HOUR The modified hour value set to 100.
	 */
	const MOD_HOUR = 100;

	/**
	 * Constant representing a modified day value.
	 *
	 * @var int MOD_DAY The modified day value set to 10000.
	 */
	const MOD_DAY = 10000;

	/**
	 * Constant representing a modified week value.
	 *
	 * @var int MOD_WEEK The modified week value set to 1000000.
	 */
	const MOD_WEEK = 1000000;

	/**
	 * Constant representing a modified month value.
	 *
	 * @var int MOD_MONTH The modified month value set to 100000000.
	 */
	const MOD_MONTH = 100000000;

	/**
	 * Constant representing the tracking impression identifier.
	 *
	 * @var string
	 */
	const TRACK_IMPRESSION = 'aatrack-records';

	/**
	 * Constant representing the tracking click identifier.
	 *
	 * @var string
	 */
	const TRACK_CLICK = 'aatrack-click';
}
