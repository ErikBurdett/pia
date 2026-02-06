<?php
/**
 * Utilities Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Utilities;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Utilities\WordPress;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Utilities\Params;

defined( 'ABSPATH' ) || exit;

/**
 * Utilities Tracking.
 */
class Tracking {

	/**
	 * Check, whether to track a specific ad or not
	 *
	 * @param Ad|int $ad      Ad instance or Ad ID.
	 * @param string $what    What to track. default value 'impression'. 'min_one' if you want to check if atleast one method is activated.
	 * @param string $context To skip role based checking for view. Default value is empty.
	 *
	 * @return bool
	 */
	public static function has_ad_tracking_enabled( $ad, $what = 'impression', $context = '' ): bool {
		// Early exit if role-based tracking is disabled and roles match.
		if ( 'view' !== $context && self::is_role_disabled() ) {
			return false;
		}

		if ( ! is_an_ad( $ad ) ) {
			$ad = wp_advads_get_ad( $ad );
		}

		if ( ! $ad ) {
			return false;
		}

		// Early exit if ad is of type 'yieldscale'.
		if ( 'view' !== $context && $ad->is_type( 'yieldscale' ) ) {
			return false;
		}

		$tracking       = $ad->get_prop( 'tracking.enabled' ) ?? 'default';
		$global_options = wp_advads_tracking()->options->get_all();

		// check for default and global settings.
		if ( 'default' === $tracking && ! empty( $global_options ) ) {
			return self::resolve_global_tracking( $global_options, $what );
		}

		// Process specific tracking options.
		return self::resolve_ad_tracking( $tracking, $what );
	}

	/**
	 * Build click tracking url.
	 *
	 * @since 1.1.0
	 *
	 * @param Ad $ad The ad object.
	 *
	 * @return string $url click tracking url
	 */
	public static function build_click_tracking_url( Ad $ad ) {
		if ( empty( $ad->get_id() ) ) {
			return '';
		}

		$linkbase  = wp_advads_tracking()->options->get( 'linkbase', Constants::DEFAULT_CLICK_LINKBASE );
		$base      = apply_filters( 'advanced-ads-tracking-click-url-base', $linkbase, $ad );
		$permalink = get_option( 'permalink_structure' );

		if ( ! $permalink ) {
			$target_url = add_query_arg( $base, $ad->get_id(), home_url( '/' ) );
		} else {
			$target_url = home_url( '/' . $base . '/' . $ad->get_id() );
			/**
			 * Hotfix caused by WPML plugin that adds variables through home_url filter
			 * but useful for similar scripts too
			 */
			$pos = strpos( $target_url, '?' );
			if ( $pos ) {
				$target_url = substr( $target_url, 0, $pos );
			}
		}

		/**
		 * Allow to manipulate the click tracking URL
		 */
		$target_url = apply_filters( 'advanced-ads-tracking-click-tracking-url', $target_url );

		return $target_url;
	}

	/**
	 * Add impression to database.
	 *
	 * @since 1.0.0
	 *
	 * @param int      $ad_id      Arguments for tracking call.
	 * @param int|null $start_time Start time of tracking request, used for metrics.
	 *
	 * @return void
	 */
	public static function track_impression( $ad_id, $start_time = null ): void {
		if ( is_array( $ad_id ) && array_key_exists( 'ad_id', $ad_id ) ) {
			$ad_id = $ad_id['ad_id'];
		}

		self::track_impressions( [ $ad_id ], $start_time );
	}

	/**
	 * Track multiple ad impressions to database.
	 *
	 * @since 1.1.0
	 *
	 * @param int[] $ad_ids     Array with ad ids as values.
	 * @param int   $start_time Timestamp when tracking started, gets passed to log.
	 *
	 * @return void
	 */
	public static function track_impressions( array $ad_ids, $start_time ): void {
		$ad_ids = self::filter_ad_ids( $ad_ids );
		if ( empty( $ad_ids ) ) {
			return;
		}

		foreach ( array_count_values( $ad_ids ) as $ad_id => $count ) {
			Database::persist( $ad_id, $count, Database::get_impression_table(), $start_time );
		}
	}

	/**
	 * Add click to database.
	 *
	 * @since 1.1.0
	 *
	 * @param int      $ad_id      The ad id to track.
	 * @param int|null $start_time Start time of tracking request, used for metrics.
	 *
	 * @return void
	 */
	public static function track_click( $ad_id, $start_time = null ): void {
		if ( is_array( $ad_id ) && array_key_exists( 'ad_id', $ad_id ) ) {
			$ad_id = $ad_id['ad_id'];
		}

		$ad_id = absint( $ad_id );
		if ( ! self::is_tracking_allowed( $ad_id, Database::get_click_table() ) ) {
			return;
		}

		Database::persist( $ad_id, 1, Database::get_click_table(), $start_time );
	}

	/**
	 * Filters the given array of ad IDs to include only those that are allowed for tracking.
	 *
	 * @param array $ad_ids An array of ad IDs to be filtered.
	 *
	 * @return array An array of ad IDs that are allowed for tracking.
	 */
	private static function filter_ad_ids( array $ad_ids ): array {
		$filtered = [];

		foreach ( $ad_ids as $ad_id ) {
			$ad_id = absint( $ad_id );
			if ( self::is_tracking_allowed( $ad_id, Database::get_impression_table() ) ) {
				$filtered[] = $ad_id;
			}
		}

		return $filtered;
	}

	/**
	 * Determine whether this impression/click should be tracked.
	 *
	 * @param int    $ad_id    The ad id to track.
	 * @param string $db_table The database table to track into.
	 *
	 * @return bool
	 */
	private static function is_tracking_allowed( $ad_id, $db_table ): bool {
		// Do not track impressions on 404 pages generated by missing css.map or js.map files.
		$uri = Params::request( 'REQUEST_URI' );
		if ( $uri && strlen( $uri ) - 4 === strpos( $uri, '.map' ) ) {
			return false;
		}

		$the_ad = wp_advads_get_ad( $ad_id );
		// Do not track expired ads click.
		if ( ! is_an_ad( $the_ad ) || $the_ad->is_expired() ) {
			return false;
		}

		/**
		 * Do not track click for bots if the options is not active.
		 * never track cache bots though
		 *
		 * TODO: remove optional bot tracking unless we find a good reason that activity by some bots should be tracked
		 */
		add_filter( 'advanced-ads-bots', [ self::class, 'add_bots_triggering_ajax' ] );

		$is_bot = Conditional::is_ua_bot();

		remove_filter( 'advanced-ads-bots', [ self::class, 'add_bots_triggering_ajax' ] );

		if (
			WordPress::is_cache_bot()
			|| Helpers::ignore_logged_in_user()
			|| ( $is_bot && ! wp_advads_tracking()->options->get( 'track-bots' ) )
		) {
			return false;
		}

		/**
		 * Allow to disable tracking something into the database.
		 *
		 * @param int    $ad_id    The ad id to track.
		 * @param string $db_table The database table to track into.
		 */
		return (bool) apply_filters( 'advanced-ads-tracking-do-tracking', true, $ad_id, $db_table );
	}

	/**
	 * Temporarily add bots that should get ads displayed but not trigger ad impressions/clicks.
	 *
	 * @param array $bots Array of bots.
	 *
	 * @return array
	 */
	public static function add_bots_triggering_ajax( array $bots ) {
		$non_tracking_bots = [ 'AspiegelBot', 'BingPreview', 'bingbot', 'datanyze', 'ecosia', 'Googlebot', 'Google-AMPHTML', 'GoogleAdSenseInfeed', 'Hexometer', 'mediapartners', '^Mozilla\/5\.0$', 'Barkrowler', 'Seekport Crawler', 'Sogou web spider', 'WP Rocket', 'FlyingPress' ];

		return array_merge( $bots, $non_tracking_bots );
	}

	/**
	 * Check if the current user role is in the disabled roles list.
	 *
	 * @return bool
	 */
	private static function is_role_disabled(): bool {
		$disabled_roles = wp_advads_tracking()->options->get( 'disabled-roles', [] );

		if ( empty( $disabled_roles ) ) {
			return false;
		}

		$user_roles = wp_get_current_user()->roles;
		return ! empty( array_intersect( $user_roles, $disabled_roles ) );
	}

	/**
	 * Resolve tracking based on global options when 'default' is specified.
	 *
	 * @param array  $global_options Global tracking options.
	 * @param string $what           The tracking action to check ('impression' or 'click').
	 *
	 * @return bool
	 */
	private static function resolve_global_tracking( array $global_options, string $what ): bool {
		$mapping = [
			'true'        => true,
			'false'       => false,
			'impressions' => 'click' !== $what,
			'clicks'      => 'impression' !== $what,
		];

		$check = $global_options['everything'] ?? 'default';
		return $mapping[ $check ] ?? true;
	}

	/**
	 * Resolve tracking for a specific ad's tracking setting.
	 *
	 * @param string $tracking The ad's tracking setting.
	 * @param string $what     The tracking action to check ('impression', 'click', 'min_one').
	 *
	 * @return bool
	 */
	private static function resolve_ad_tracking( string $tracking, string $what ): bool {
		$mapping = [
			'enabled'     => true,
			'disabled'    => false,
			'impressions' => 'click' !== $what || 'min_one' === $what,
			'clicks'      => 'impression' !== $what || 'min_one' === $what,
		];

		return $mapping[ $tracking ] ?? true;
	}
}
