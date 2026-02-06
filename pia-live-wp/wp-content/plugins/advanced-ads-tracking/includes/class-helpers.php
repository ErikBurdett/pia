<?php
/**
 * Helpers for the tracking module.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use Advanced_Ads;
use Advanced_Ads_Utils;
use Advanced_Ads_Privacy;
use AdvancedAds\Tracking\Utilities\Data;

/**
 * Helpers.
 */
class Helpers {

	/** Conditional -------------------------------- */

	/**
	 * Check if the "PopUp and Layer Ads" or "Sticky Ads" add-ons are active.
	 *
	 * @return bool
	 */
	public static function has_delayed_ads(): bool {
		return defined( 'AAPLDS_BASE_PATH' ) || defined( 'AASADS_BASE_PATH' );
	}

	/**
	 * Whether we have a conflict between TCF and tracking method.
	 *
	 * @return bool
	 */
	public static function has_tcf_conflict(): bool {
		$method          = wp_advads_tracking()->options->get( 'method', 'onrequest' );
		$privacy_options = Advanced_Ads_Privacy::get_instance()->options();

		return ! empty( $privacy_options['enabled'] )
			&& 'iab_tcf_20' === $privacy_options['consent-method']
			&& in_array( $method, [ 'onrequest', 'shutdown' ], true );
	}

	/**
	 * Check if the legacy ajax method is explicitly set.
	 *
	 * @return bool
	 */
	public static function is_legacy_ajax(): bool {
		return defined( 'ADVANCED_ADS_TRACKING_LEGACY_AJAX' ) && ADVANCED_ADS_TRACKING_LEGACY_AJAX;
	}

	/**
	 * Check if the given type is clickable.
	 *
	 * @param string $type The type to check.
	 *
	 * @return bool True if the type is clickable, false otherwise.
	 */
	public static function is_clickable_type( $type ): bool {
		return in_array( $type, Data::get_clickable_types(), true );
	}

	/**
	 * Check if the parallel tracking with GA is set.
	 *
	 * @return bool
	 */
	public static function is_forced_analytics(): bool {
		return defined( 'ADVANCED_ADS_TRACKING_FORCE_ANALYTICS' ) && ADVANCED_ADS_TRACKING_FORCE_ANALYTICS;
	}

	/**
	 * Checks if the provided tracking method matches the current tracking method.
	 *
	 * @param string $method The tracking method to check.
	 *
	 * @return bool True if the provided method matches the current tracking method, false otherwise.
	 */
	public static function is_tracking_method( $method ): bool {
		return self::get_tracking_method() === $method;
	}

	/**
	 * Return true if this is a logged-in user and those should not be tracked
	 * based on constant ADVANCED_ADS_TRACKING_IGNORE_LOGGED_IN_USERS
	 *
	 * @return bool true, if current interaction should not be tracked
	 */
	public static function ignore_logged_in_user(): bool {
		return defined( 'ADVANCED_ADS_TRACKING_IGNORE_LOGGED_IN_USERS' ) && ADVANCED_ADS_TRACKING_IGNORE_LOGGED_IN_USERS && is_user_logged_in();
	}

	/**
	 * Don't show stats submenu when GA is tracking method and stats db empty.
	 *
	 * @return bool Whether to show the stats submenu.
	 */
	public static function can_show_stats(): bool {
		static $show_stats;

		if ( null === $show_stats ) {
			$show_stats = ! (
				self::is_tracking_method( 'ga' )
				&& empty( array_filter( Database::get_sums() ) )
			);
		}

		return $show_stats;
	}

	/** Getter -------------------------------- */

	/**
	 * Get the target link
	 *
	 * @param Ad|int $ad ID of the ad or the ad object.
	 *
	 * @return string link if given or empty string
	 */
	public static function get_ad_link( $ad ): string {
		if ( ! is_an_ad( $ad ) ) {
			$ad = wp_advads_get_ad( $ad );
		}

		$link = $ad->get_prop( 'tracking.link' );
		if ( ! empty( $link ) ) {
			return $link;
		}

		$link = $ad->get_prop( 'url' );
		if ( ! empty( $link ) ) {
			return $link;
		}

		return '';
	}

	/**
	 * Get the target attribute for the link, e.g. ` target="_blank"`
	 *
	 * @param Ad   $ad         ID of the ad or the ad object.
	 * @param bool $value_only If true only return value, if false return fall attribute as string.
	 *
	 * @return string whole target attribute with value
	 */
	public static function get_ad_target( $ad, $value_only = false ): string {
		if ( ! is_an_ad( $ad ) ) {
			$ad = wp_advads_get_ad( $ad );
		}

		$ad_target       = $ad->get_prop( 'tracking.target' ) ?? '';
		$target          = wp_advads_tracking()->options->get( 'target', '0' );
		$general_options = Advanced_Ads::get_instance()->options();

		/**
		 * Second line is needed for backward compatibility with Tracking 1.7.2 and below when the general target-blank option was still in this add-on and not in basic
		 * and below when the general target-blank option was still in this add-on and not in basic
		 */
		$general_target_blank = ( isset( $general_options['target-blank'] ) && '1' === $general_options['target-blank'] )
								|| '1' === $target;
		if (
			( $general_target_blank && 'same' !== $ad_target ) ||
			'new' === $ad_target
		) {
			return $value_only ? '_blank' : ' target="_blank"';
		}

		return '';
	}

	/**
	 * Generates a URL for the Advanced Ads Tracking database tool page in the WordPress admin area.
	 *
	 * @return string The URL to the Advanced Ads Tracking database tool page.
	 */
	public static function get_database_tool_link(): string {
		return add_query_arg(
			'page',
			'advads-tracking-db-page',
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Retrieves the default tracking method's name based on options.
	 *
	 * @return string The name of the default tracking method.
	 */
	public static function get_default_tracking_method(): string {
		// Fetch options using the options() method.
		$option = wp_advads_tracking()->options->get( 'everything' );

		// Early bail!!
		if ( ! $option ) {
			return esc_html__( 'disabled', 'advanced-ads-tracking' );
		}

		// tracking methods.
		$tracking_choices = [
			'impressions' => esc_html__( 'impressions only', 'advanced-ads-tracking' ),
			'clicks'      => esc_html__( 'clicks only', 'advanced-ads-tracking' ),
			'true'        => esc_html__( 'impressions & clicks', 'advanced-ads-tracking' ),
		];

		// Check if the 'everything' key exists in options.
		return $tracking_choices[ $option ] ?? esc_html__( 'disabled', 'advanced-ads-tracking' );
	}

	/**
	 * Get the tracking method.
	 * Default is `frontend` (AJAX).
	 *
	 * @param string $method Pass a method that should be filtered.
	 *
	 * @return string
	 */
	public static function get_tracking_method( $method = '' ): string {
		$valid_methods = [ 'frontend', 'ga', 'onrequest' ];

		if ( empty( $method ) ) {
			$options = wp_advads_tracking()->options->get_all();
			$method  = isset( $options['method'] ) && is_string( $options['method'] ) ? $options['method'] : '';
		}

		if ( empty( $method ) || ! in_array( $method, $valid_methods, true ) ) {
			$method = 'frontend';
		}

		/**
		 * Filter the tracking method in use.
		 *
		 * @param string $method
		 */
		return (string) apply_filters( 'advanced-ads-tracking-method', $method );
	}

	/**
	 * Extract custom urls from string
	 *
	 * @param string $content HTML string.
	 *
	 * @return array Array of urls or empty array.
	 */
	public static function get_url_from_string( $content ) {
		$regex = '#\bhttps?://[^\s()<>]+(?:\(\w+\)|([^[:punct:]\s]|/))#';
		preg_match_all( $regex, $content, $matches );

		return $matches[0];
	}

	/**
	 * Get the link cloaking base from the database or the default value and allow filtering.
	 *
	 * @return string
	 */
	public static function get_link_base(): string {
		static $link_base;

		if ( ! isset( $link_base ) ) {
			$link_base = wp_advads_tracking()->options->get( 'linkbase', Constants::DEFAULT_CLICK_LINKBASE );

			/**
			 * Filter the click url/link cloaking fragment.
			 *
			 * @param string $link_base The current fragment from options or default value.
			 */
			$link_base = (string) apply_filters( 'advanced-ads-tracking-click-url-base', $link_base, false );
		}

		return $link_base;
	}

	/**
	 * Get the slug for public stats page.
	 *
	 * @return string
	 */
	public static function get_public_stats_slug(): string {
		return wp_advads_tracking()->options->get( 'public-stats-slug', Constants::DEFAULT_PUBLIC_STATS_SLUG );
	}

	/**
	 * Get the timestamp in format YmWdH (H defaults to 06 to only have one timestamp per day).
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $timestamp reference time (default: now; server time).
	 * @param bool     $fixed     whether to return a fixed hour (on stat per day per ad).
	 *
	 * @return string  db formatted timestamp in WordPress local time
	 */
	public static function get_timestamp( $timestamp = null, $fixed = false ): string {
		// This specific format is required to work with WordPress core < 5.3.0.
		$time = gmdate( 'Y-m-d H:i:s', (int) $timestamp < 1 ? time() : $timestamp );

		preg_match( '/(?<y>\d{2})(?<m>\d{2})(?<W>\d{2})(?<d>\d{2})(?<H>\d{2})/', get_date_from_gmt( $time, 'ymWdH' ), $timestamp_exploded );
		$timestamp_exploded = array_filter( $timestamp_exploded, 'is_string', ARRAY_FILTER_USE_KEY );
		$week               = (int) $timestamp_exploded['W'];
		$month              = (int) $timestamp_exploded['m'];

		// Check for week/month inconsistencies.
		if ( $week >= 52 && 1 === $month ) {
			// Still week 52 but already in January.
			$timestamp_exploded['W'] = '01';
		} elseif ( $week > 52 && 12 === $month ) {
			// Still in December but week 53.
			$timestamp_exploded['W'] = '52';
		}

		if ( $fixed ) {
			$timestamp_exploded['H'] = '06';
		}

		return implode( '', $timestamp_exploded );
	}

	/**
	 * Format original date as stored in db for display.
	 *
	 * @param int    $db_time db time.
	 * @param string $format  date format.
	 *
	 * @return string
	 */
	public static function get_date_from_db( $db_time, $format ): string {
		$date = array_combine( [ 'year', 'month', 'week', 'day', 'hour' ], str_split( $db_time, 2 ) );
		// TODO: since month and day have special meaning when `0` this was hot-fixed.
		$time = mktime( (int) $date['hour'], 0, 0, max( $date['month'], 1 ), max( $date['day'], 1 ), (int) $date['year'] );

		return date( $format, $time ); // phpcs:ignore
	}

	/** Sanitizer -------------------------------- */

	/**
	 * Sanitize the report period.
	 *
	 * @param string $period The period to sanitize.
	 *
	 * @return string The sanitized period.
	 */
	public static function sanitize_report_period( $period ): string {
		$valid_periods = [ 'last30days', 'lastmonth', 'last12months' ];

		return $period && in_array( $period, $valid_periods, true ) ? $period : 'last30days';
	}

	/**
	 * Sanitize the report frequency.
	 *
	 * @param string $frequency The frequency to sanitize.
	 *
	 * @return string The sanitized frequency.
	 */
	public static function sanitize_report_frequency( $frequency ): string {
		$valid_frequencies = [ 'never', 'daily', 'weekly', 'monthly' ];

		return $frequency && in_array( $frequency, $valid_frequencies, true ) ? $frequency : 'never';
	}

	/**
	 *  Get the url to the admin stats for the last 30 days for a given ad ID
	 *
	 * @param int $id ad id.
	 *
	 * @return string
	 */
	public static function stats_url_admin_30days( $id ): string {
		$today       = time();
		$wp_timezone = Advanced_Ads_Utils::get_wp_timezone();
		$stat_from   = date_create( '@' . ( $today - ( 29 * 24 * 60 * 60 ) ), $wp_timezone );
		$stat_to     = date_create( '@' . $today, $wp_timezone );

		return add_query_arg(
			[
				'page'                  => 'advanced-ads-stats',
				'advads-stats[period]'  => 'custom',
				'advads-stats[groupby]' => 'day',
				'advads-stats[ads]'     => 'all-ads',
				'advads-stats[from]'    => $stat_from->format( 'm/d/Y' ),
				'advads-stats[to]'      => $stat_to->format( 'm/d/Y' ),
				'advads-stats-filter[]' => $id,
			],
			admin_url( 'admin.php' )
		);
	}
}
