<?php
/**
 * Utilities Data.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Utilities;

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Tracking\Installation\Tracking_Installer;

defined( 'ABSPATH' ) || exit;

/**
 * Utilities Data.
 */
class Data {

	/**
	 * Adds test data for given ad ids
	 *
	 * @param array $ids      Array of ad ids.
	 * @param int   $max_days number of days to create test data beginning from today.
	 * @param int   $i        base number for test data.
	 * @param int   $runs     run multiple times if you want more test data.
	 *
	 * @noinspection PhpUnused
	 */
	public static function create_test_data( $ids, $max_days = 600, $i = 1000, $runs = 1 ) {
		global $wpdb;

		// Early bail!!
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$max_hours  = $max_days * 24 - 1;
		$variance   = 4;
		$num_ids    = (int) max( 2, count( $ids ) / $variance );
		$base_time  = time();
		$base_time -= $base_time % 3600;

		// Run $runs times.
		for ( $y = 0; $y < $runs; $y++ ) {
			// Define arrays to save data.
			$values        = [];
			$values_clicks = [];
			$place_holders = [];
			for ( $n = $i; $n > 0; $n-- ) {

				// Create random date in given period.
				$ts = $base_time - 3600 * wp_rand( 0, $max_hours / $variance / 10 ) * wp_rand( 1, $variance * 10 );
				$ts = Helpers::get_timestamp( $ts, true );

				// Get random ad ids but minimum 2.
				$sub_ids = array_rand( $ids, wp_rand( 2, $num_ids ) );
				foreach ( $sub_ids as $sub_id ) {
					$sub_id   = $ids[ $sub_id ];
					$tendency = 1 + ( crc32( $sub_id ) % 100 ) / 100;

					$impressions = ( ( wp_rand( 0, 10 ) + wp_rand( 0, 10 ) ) * wp_rand( 1, 5 ) );
					array_push( $values, $ts, $sub_id, $impressions );
					// generate clicks with approximately 3% of impressions, varying between -70% and +100%.
					array_push( $values_clicks, $ts, $sub_id, round( $impressions * $tendency * 0.015 * ( wp_rand( 30, 200 ) / 100 ) ) );

					$place_holders[] = "('%d', '%d', '%d')";
				}
			}

			// phpcs:disable
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO ' . Database::get_impression_table() . ' (timestamp, ad_id, count) VALUES ' . implode( ', ', $place_holders ) . ' ON DUPLICATE KEY UPDATE count=(count + VALUES(count)) / 2',
					$values
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO ' . Database::get_click_table() . ' (timestamp, ad_id, count) VALUES ' . implode( ', ', $place_holders ) . ' ON DUPLICATE KEY UPDATE count=(count + VALUES(count)) / 2',
					$values_clicks
				)
			);
			// phpcs:enable
		}
	}

	/**
	 *  Collect data on blog from which ads have been picked
	 *
	 * @return array
	 */
	public static function collect_blog_data(): array {
		static $blog_data = [
			'ajaxurls'         => [],
			'gaUIDs'           => [],
			'gaAnonymIP'       => [],
			'methods'          => [],
			'linkbases'        => [],
			'allads'           => [],
			'parallelTracking' => [],
		];

		$blog_id      = get_current_blog_id();
		$ajax_handler = new Tracking_Installer();
		if ( ! isset( $blog_data['ajaxurls'][ $blog_id ] ) ) {
			$handler = $ajax_handler->get_handler_url();
			if ( Helpers::is_legacy_ajax() || ! $ajax_handler->handler_exists() ) {
				$handler = admin_url( 'admin-ajax.php' );
			}

			if ( ! Helpers::ignore_logged_in_user() ) {
				$blog_data['ajaxurls'][ $blog_id ] = $handler;
			}
		}

		$options = get_option( Constants::OPTIONS_SLUG, [] );
		if ( ! isset( $blog_data['gaUIDs'][ $blog_id ] ) ) {
			$blog_data['gaUIDs'][ $blog_id ] = array_map( 'trim', wp_parse_list( $options['ga-UID'] ?? '' ) );
		}

		if ( ! isset( $blog_data['gaAnonymIP'][ $blog_id ] ) ) {
			$blog_data['gaAnonymIP'][ $blog_id ] = isset( $options['ga-anonym-IP'] ) && 'on' === $options['ga-anonym-IP'];
		}

		if ( ! isset( $blog_data['methods'][ $blog_id ] ) ) {
			$blog_data['methods'][ $blog_id ] = Helpers::get_tracking_method( $options['method'] );
		}

		$blog_data['parallelTracking'][ $blog_id ] = Helpers::is_forced_analytics();

		if ( ! isset( $blog_data['linkbases'][ $blog_id ] ) ) {
			$permalink = get_option( 'permalink_structure' );
			$link_base = Helpers::get_link_base();

			if ( ! empty( $permalink ) ) {
				$link_base = trailingslashit( home_url( $link_base ) );
			}

			$blog_data['linkbases'][ $blog_id ] = $link_base;
		}

		if ( ! isset( $blog_data['allads'][ $blog_id ] ) ) {
			$ads = wp_advads_ad_query(
				[
					'fields'      => 'ids',
					'post_status' => [ 'publish', 'future', 'draft', 'pending' ],
				]
			)->posts;
			foreach ( $ads as $ad_id ) {
				$ad = wp_advads_get_ad( $ad_id );

				if ( ! $ad ) {
					continue;
				}

				$blog_data['allads'][ $blog_id ][ (string) $ad_id ] = [
					'title'      => $ad->get_title(),
					'target'     => Helpers::get_ad_link( $ad ),
					'impression' => Tracking::has_ad_tracking_enabled( $ad ),
					'click'      => Tracking::has_ad_tracking_enabled( $ad, 'click' ),
				];
			}
		}

		return $blog_data;
	}

	/**
	 * Return a filterable list of clickable types.
	 *
	 * @return array
	 */
	public static function get_clickable_types() {
		$types = [ 'plain', 'dummy', 'content', 'image', 'adsense', 'gam' ];

		/**
		 * Filter clickable types.
		 *
		 * @param array default clickable types.
		 */
		return (array) apply_filters( 'advanced-ads-tracking-clickable-types', $types );
	}

	/**
	 * Retrieve an array of predefined time periods for tracking purposes.
	 *
	 * @return array An associative array where the keys are period identifiers and the values are translated period names.
	 */
	public static function get_periods(): array {
		return [
			'today'     => __( 'today', 'advanced-ads-tracking' ),
			'yesterday' => __( 'yesterday', 'advanced-ads-tracking' ),
			'last7days' => __( 'last 7 days', 'advanced-ads-tracking' ),
			'thismonth' => __( 'this month', 'advanced-ads-tracking' ),
			'lastmonth' => __( 'last month', 'advanced-ads-tracking' ),
			'thisyear'  => __( 'this year', 'advanced-ads-tracking' ),
			'lastyear'  => __( 'last year', 'advanced-ads-tracking' ),
			// TODO: this is not fully supported for ranges of more than ~200 points; should be reviewed before 2015-09-01
			'custom'    => __( 'custom', 'advanced-ads-tracking' ),
		];
	}
}
