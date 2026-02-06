<?php
/**
 * The class hold database queries.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use Exception;
use DateInterval;
use AdvancedAds\Ads\Ad_Repository;
use AdvancedAds\Constants as AdvAds_Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Class Database
 */
class Database {

	/**
	 * Get the impressions table name of the current blog on normal site or a multi site
	 * When loading ads from another blog of a multi site, we get the updated value.
	 *
	 * @return string
	 */
	public static function get_impression_table() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . Constants::TABLE_IMPRESSIONS;
	}

	/**
	 * Get the clicks table name of the current blog on normal site or a multi site
	 * When loading ads from another blog of a multi site, we get the updated value.
	 *
	 * @return string
	 */
	public static function get_click_table() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . Constants::TABLE_CLICKS;
	}

	/**
	 * Get all ads id.
	 *
	 * @param string $fetch Fetch posts, ids or dropdown(post_title => ID).
	 *
	 * @return array
	 */
	public static function get_all_ads( $fetch = 'posts' ): array {
		static $tracking_ads = null;

		if ( null === $tracking_ads ) {
			$query        = wp_advads_ad_query(
				[
					'post_status' => [ 'publish', 'future', 'draft', 'pending', AdvAds_Constants::AD_STATUS_EXPIRED ],
				]
			);
			$tracking_ads = $query->have_posts() ? $query->posts : [];
		}

		if ( 'ids' === $fetch ) {
			return wp_list_pluck( $tracking_ads, 'ID' );
		}

		if ( 'dropdown' === $fetch ) {
			return wp_list_pluck( $tracking_ads, 'post_title', 'ID' );
		}

		return $tracking_ads;
	}

	/**
	 * Get the ad id by the public hash.
	 *
	 * @param string $hash The public id for the ad.
	 *
	 * @return int|false
	 */
	public static function get_ad_by_hash( $hash ) {
		$query = wp_advads_ad_query(
			[
				'fields'      => 'ids',
				'post_status' => [ 'publish', 'future', 'draft', 'pending', AdvAds_Constants::AD_STATUS_EXPIRED ],
				'meta_query'  => [ // phpcs:ignore
					[
						'key'     => Ad_Repository::OPTION_METAKEY,
						'value'   => $hash,
						'compare' => 'LIKE',
					],
				],
			]
		);

		return $query->have_posts() ? $query->posts[0] : false;
	}

	/**
	 * Retrieves the total number of clicks for a given ad.
	 *
	 * @param int $ad_id The ID of the ad for which to retrieve the total clicks.
	 *
	 * @return int The total number of clicks for the specified ad. Returns 0 if no clicks are found.
	 */
	public static function get_ad_total_clicks( $ad_id ): int {
		global $wpdb;

		$table_name  = self::get_click_table();
		$impressions = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT SQL_NO_CACHE SUM(count) FROM $table_name WHERE ad_id = %d;", // phpcs:ignore
				$ad_id
			)
		);

		return $impressions ? (int) $impressions : 0;
	}

	/**
	 * Retrieves the total number of impressions for a given ad.
	 *
	 * @param int $ad_id The ID of the ad for which to retrieve the total impressions.
	 *
	 * @return int The total number of impressions for the specified ad. Returns 0 if no impressions are found.
	 */
	public static function get_ad_total_impressions( $ad_id ): int {
		global $wpdb;

		$table_name  = self::get_impression_table();
		$impressions = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT SQL_NO_CACHE SUM(count) FROM $table_name WHERE ad_id = %d;", // phpcs:ignore
				$ad_id
			)
		);

		return $impressions ? (int) $impressions : 0;
	}

	/**
	 * Load sums of impressions and clicks.
	 *
	 * @since 1.2.6
	 *
	 * @return array with impressions and clicks by ad id.
	 */
	public static function get_sums() {
		global $wpdb;

		static $sums = null;

		// Early bail!!
		if ( null !== $sums ) {
			return $sums;
		}

		$sums = [
			'impressions' => [],
			'clicks'      => [],
		];

		$metrics =
		[
			'clicks'      => self::get_click_table(),
			'impressions' => self::get_impression_table(),
		];

		foreach ( $metrics as $metric => $table ) {
			if ( ! $wpdb->query( "SHOW TABLES LIKE '{$table}'" ) ) { // phpcs:ignore
				continue;
			}

			$result = $wpdb->query( "SELECT SQL_NO_CACHE `ad_id`, SUM(`count`) as `count` FROM  {$table} GROUP BY `ad_id`" ); // phpcs:ignore
			if ( $result ) {
				foreach ( $wpdb->last_result as $row ) {
					$sums[ $metric ][ $row->ad_id ] = $row->count;
				}
			}
		}

		return $sums;
	}

	/**
	 * Get the sums for an ad from the db, not the cached value.
	 *
	 * @param int  $ad_id      The ad id.
	 * @param bool $use_clicks Whether to get stats for clicks.
	 *
	 * @return array
	 */
	public static function get_sums_for_ad( $ad_id, $use_clicks = false ) {
		return [
			'impressions' => self::get_ad_total_impressions( $ad_id ),
			'clicks'      => $use_clicks ? self::get_ad_total_clicks( $ad_id ) : 0,
		];
	}

	/**
	 * Load stats from the tracking tables
	 *
	 * @since 2.6.0
	 *
	 * @param array  $args  Argument to load stats.
	 *                      `ad_id` empty array if all ads.
	 * @param string $table name of the table.
	 *
	 * @return array $stats array with stats sorted by date
	 */
	public static function load_stats( $args, $table ): array {
		global $wpdb;

		if ( ! isset( $args['ad_id'] ) ) {
			return [];
		}

		$stats  = [];
		$ad_ids = 'all' === $args['ad_id']
			? self::get_all_ads( 'ids' )
			: ( is_array( $args['ad_id'] ) ? array_values( $args['ad_id'] ) : [] );

		$table   = ' `' . $wpdb->_real_escape( str_replace( '`', '_', $table ) ) . '`';
		$where   = [ 'WHERE 1 = 1' ];
		$select  = 'SQL_NO_CACHE `ad_id`, SUM(`count`) as `impressions`, %s as `date`';
		$orderby = ''; // 'ORDER BY `timestamp` ASC'; // this is implicit for current model

		list( $groupby, $select_timestamp, $date_format, $group_increment ) = self::stats_group_by( $args );
		list( $start, $end ) = self::stats_range( $args );

		if ( ! empty( $start ) ) {
			$where[] = "AND timestamp >= $start";
		}

		if ( ! empty( $end ) ) {
			$where[] = "AND timestamp < $end";
		}

		/**
		 * Select only one ad stats
		 */
		$ad_count = count( $ad_ids );
		if ( 1 === $ad_count ) {
			$where[] = 'AND ad_id = ' . $ad_ids[0];
		} elseif ( $ad_count > 1 ) {
			$where[] = 'AND ad_id IN (' . implode( ',', $ad_ids ) . ')';
		}

		$where    = implode( ' ', $where );
		$select   = sprintf( $select, ! empty( $select_timestamp ) ? $select_timestamp : $groupby );
		$groupby .= ', `ad_id`';

		// Fetch stats from the database.
		$num_rows  = $wpdb->query( "SELECT $select FROM $table $where $orderby GROUP BY $groupby" ); // phpcs:ignore
		$stat_base = [];

		if ( $num_rows > 0 ) {
			$rows = $wpdb->last_result;

			if ( [] !== $ad_ids ) {
				foreach ( $ad_ids as $ad_id ) {
					$stat_base[ $ad_id ] = null;
				}
			}

			foreach ( $rows as $row ) {
				$time = Helpers::get_date_from_db( $row->date, $date_format );
				if ( ! isset( $stats[ $time ] ) ) {
					$stats[ $time ] = $stat_base;
				}

				// TODO: may select ad_id from row, if defined
				// TODO: click table currently also has "impressions" instead of "clicks" in order to handle both tables equally.
				if ( isset( $stats[ $time ][ $row->ad_id ] ) ) {
					$stats[ $time ][ $row->ad_id ] += $row->impressions;
				} else {
					$stats[ $time ][ $row->ad_id ] = $row->impressions;
				}
			}
		}

		if ( empty( $stats ) ) {
			return [];
		}

		try {
			return self::prepare_stats_array( $stats, $stat_base, $date_format, $group_increment );
		} catch ( Exception $e ) {
			return [];
		}

		return [];
	}

	/**
	 * Write impression/click track into db.
	 *
	 * @param int    $id         The ad id.
	 * @param int    $count      Number of impressions (always 1 for clicks).
	 * @param string $table      The table name to track into (including wpdb_prefix).
	 * @param null   $start_time The starting time, used in debug log.
	 */
	public static function persist( $id, $count, $table, $start_time = null ) {
		global $wpdb;

		$timestamp = Helpers::get_timestamp( null, true );
		$success   = $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (`ad_id`, `timestamp`, `count`) VALUES (%d, %d, %d) ON DUPLICATE KEY UPDATE `count` = `count` + %d", $id, $timestamp, $count, $count ) ); // phpcs:ignore

		/**
		 * Add custom logging if ADVANCED_ADS_TRACKING_DEBUG is enabled
		 * writes events into wp-content/advanced-ads-tracking.csv
		 */
		if ( Debugger::debugging_enabled( $id ) ) {
			Debugger::log( $id, $table, ! is_null( $start_time ) ? round( ( microtime( true ) - $start_time ) * 1000 ) : - 1 );
		}

		/**
		 * Allow to perform your own action when tracking was performed locally
		 *
		 * @param int    $id        The ad id tracked.
		 * @param string $table     name of the table, normally {prefix_}advads_impressions or {prefix_}advads_clicks.
		 * @param int    $timestamp The timestamp of the save.
		 * @param bool   $success   If written into db.
		 */
		do_action( 'advanced-ads-tracking-after-writing-into-db', $id, $table, $timestamp, $success );
	}

	/**
	 * Group stats by day, week or month
	 *
	 * @param array $args Arguments to group stats.
	 *
	 * @return array
	 */
	private static function stats_group_by( $args ): array {
		$groupby          = '`timestamp`';
		$select_timestamp = null;
		$date_format      = $args['groupFormat'] ?? 'Y-m-d';
		$group_increment  = ' + 1 day';

		if ( isset( $args['groupby'] ) ) {
			switch ( $args['groupby'] ) {
				case 'day':
					$groupby         = '`timestamp` - `timestamp` % ' . Constants::MOD_HOUR;
					$group_increment = ' + 1 day';
					break;

				case 'week':
					// rather complex to mind weeks overlapping month and year while keeping proper display dates
					// Y + W + MW == 0152 | 1201 ?
					// Year + 00 + Week + 00 + 0 + ( MW == 0152 || MW == 1201 ).
					$groupby          =
						'(`timestamp` - `timestamp` % ' . Constants::MOD_MONTH // year.
						. ') + (`timestamp` - `timestamp` % ' . Constants::MOD_DAY // year + month + week.
						. ') - (`timestamp` - `timestamp` % ' . Constants::MOD_WEEK // - year - month.
						. ') + ('
						. '(`timestamp` - `timestamp` % ' . Constants::MOD_DAY // + year + month + week.
						. '- `timestamp` % ' . Constants::MOD_MONTH // - year.
						. ') IN (1520000, 12010000))';
					$select_timestamp = '`timestamp` - `timestamp` % ' . Constants::MOD_HOUR;
					$group_increment  = ' + 1 week';
					break;

				case 'month':
					$groupby         = '`timestamp` - `timestamp` % ' . Constants::MOD_WEEK;
					$group_increment = ' + 1 month';
					break;
			}
		}

		return [
			$groupby,
			$select_timestamp,
			$date_format,
			$group_increment,
		];
	}

	/**
	 * Get the start and end timestamp for a given period.
	 *
	 * @param array $args Arguments to get the range.
	 *
	 * @return array
	 */
	private static function stats_range( $args ): array {
		$start = null;
		$end   = null;

		if ( isset( $args['period'] ) ) {
			$now         = Helpers::get_timestamp();
			$gmt_offset  = 3600 * (float) get_option( 'gmt_offset', 0 );
			$today_start = $now - $now % Constants::MOD_HOUR;

			switch ( $args['period'] ) {
				case 'today':
					$start = $today_start;
					break;

				case 'yesterday':
					$start  = Helpers::get_timestamp( time() - DAY_IN_SECONDS );
					$start -= $start % Constants::MOD_HOUR;
					$end    = $today_start;
					break;

				case 'last7days':
					$start  = Helpers::get_timestamp( time() - ( WEEK_IN_SECONDS + DAY_IN_SECONDS ) );
					$start -= $start % Constants::MOD_HOUR;

					// Get yestarday date.
					$end  = Helpers::get_timestamp( time() - DAY_IN_SECONDS );
					$end -= $start % Constants::MOD_HOUR;
					break;

				case 'thismonth':
					$start = $now - $now % Constants::MOD_WEEK;
					break;

				case 'lastmonth':
					$start = Helpers::get_timestamp( mktime( 0, 0, 0, gmdate( 'm' ) - 1, 1, gmdate( 'Y' ) ) );
					$end   = $now - $now % Constants::MOD_WEEK;
					break;

				case 'thisyear':
					$start = $now - $now % Constants::MOD_MONTH;
					break;

				case 'lastyear':
					$start = Helpers::get_timestamp( mktime( 0, 0, 0, 1, 1, gmdate( 'Y' ) - 1 ) );
					$end   = $now - $now % Constants::MOD_MONTH;
					break;

				case 'custom':
					$start = Helpers::get_timestamp( strtotime( $args['from'] ) - $gmt_offset );
					$end   = Helpers::get_timestamp( strtotime( $args['to'] ) - $gmt_offset + ( 24 * 3600 ) );
					break;
			}
		}

		return [ $start, $end ];
	}

	/**
	 * Prepare the stats array for templating.
	 * Especially add empty dates.
	 *
	 * @throws Exception Throw exception for DateInterval.
	 *
	 * @param array  $stats           Graph values by timestamp (grouped).
	 * @param array  $stat_base       Empty stat row.
	 * @param string $group_format    Date format string (x-axis labels).
	 * @param string $group_increment Date increment string.
	 *
	 * @return array $stats input with filled in dates.
	 */
	private static function prepare_stats_array( $stats, $stat_base, $group_format, $group_increment ): array {
		if ( empty( $stats ) ) {
			return [];
		}

		$old_time = null;
		$time     = null;

		// Ensure order.
		$stat_keys = array_keys( $stats );
		natsort( $stat_keys );
		$sorted_stats = [];

		$increment_interval = [
			' + 1 day'   => 'P1D',
			' + 1 week'  => 'P1W',
			' + 1 month' => 'P1M',
		];

		$prev_date = null;

		$date_format = 'Y-m-d';
		if ( ' + 1 month' === $group_increment || 'o-\\WW' === $group_format ) {
			$date_format = $group_format;
		}

		// if PHP earlier than 5.3.0 return result directly.
		if ( PHP_VERSION_ID < 50300 ) {
			return $sorted_stats;
		}

		foreach ( $stat_keys as $stat_key ) {
			$current_date = date_create( $stat_key );
			// Fill missing entry for date w/o records.
			if ( ! is_null( $prev_date ) ) {
				$next_date = clone $prev_date;
				$next_date->add( new DateInterval( $increment_interval[ $group_increment ] ) );

				if ( $next_date < $current_date && $stat_key !== $next_date->format( $date_format ) ) {
					while ( $next_date->format( $date_format ) !== $stat_key && ! ( $next_date > $current_date ) ) {
						$sorted_stats[ $next_date->format( $date_format ) ] = $stat_base;
						$next_date->add( new DateInterval( $increment_interval[ $group_increment ] ) );
					}
				}
			}

			$sorted_stats[ $stat_key ] = $stats[ $stat_key ];
			$prev_date                 = clone $current_date;
		}

		return $sorted_stats;
	}
}
