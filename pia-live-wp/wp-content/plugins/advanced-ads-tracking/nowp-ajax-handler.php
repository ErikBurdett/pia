<?php
/**
 * Fast AJAX endpoint for Advanced Ads Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 *
 * If you wish not to use this tracking method, please set the constant ADVANCED_ADS_TRACKING_LEGACY_AJAX,
 * i.e. define( 'ADVANCED_ADS_TRACKING_LEGACY_AJAX', true ) in your wp-config.php
 */

define( 'SHORTINIT', true );

// Load minimal WordPress environment.
require_once '%4$swp-load.php';
require_once '%5$s';

use AdvancedAds\Tracking\Constants;
use AdvancedAds\Tracking\Debugger;

// phpcs:disable WordPress.DB.RestrictedFunctions
// phpcs:disable WordPress.Security.NonceVerification
// phpcs:disable WordPress.DateTime.RestrictedFunctions
// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
// phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found

/**
 * Class AdvancedAdsTracker
 *
 * Lightweight, high-performance tracker for ad impressions and clicks.
 * Optimized for use with SHORTINIT in WordPress to minimize overhead.
 */
class AdvancedAds_Fast_Tracker {

	/**
	 * Start time of the request in microseconds.
	 *
	 * @var int
	 */
	private $start_time;

	/**
	 * Data received from the request.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Regex pattern to detect bots by user agent.
	 *
	 * @var string $bots
	 */
	private $bots = '%6$s';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->start_time = microtime( true );
		$this->set_headers();
		$this->load_input();
		$this->prevent_bots();
		$this->process_tracking();
	}

	/**
	 * Sets HTTP headers to avoid caching and enhance privacy.
	 *
	 * @return void
	 */
	private function set_headers(): void {
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Accel-Expires: 0' );
		header( 'X-Robots-Tag: noindex' );
		header_remove( 'Last-Modified' );
		flush();
		@ignore_user_abort( true );
	}

	/**
	 * Retrieves and validates input from GET, POST, or JSON.
	 *
	 * @return void
	 */
	private function load_input(): void {
		$method     = strtolower( $_SERVER['REQUEST_METHOD'] );
		$this->data = 'get' === $method ? $_GET : $_POST;
		$this->data = is_array( $this->data ) ? wp_unslash( $this->data ) : [];

		// Fallback: check for JSON body.
		if ( empty( $this->data ) ) {
			$json   = file_get_contents( 'php://input' );
			$parsed = json_decode( $json, true );
			if ( is_array( $parsed ) ) {
				$this->data = array_merge( $this->data, $parsed );
			}
		}

		if ( ! isset( $this->data['ads'] ) || ! is_array( $this->data['ads'] ) ) {
			die( 'no ads' );
		}

		$this->data['ads'] = array_filter(
			array_map( 'intval', $this->data['ads'] )
		);

		if ( empty( $this->data['ads'] ) ) {
			die( 'no ads' );
		}

		if (
			empty( $this->data['action'] )
			|| ! in_array( $this->data['action'], [ Constants::TRACK_IMPRESSION, Constants::TRACK_CLICK ], true )
		) {
			die( 'nothing to do' );
		}
	}

	/**
	 * Checks the user agent to block bot requests based on regex.
	 *
	 * @return void
	 */
	private function prevent_bots(): void {
		if ( empty( $this->bots ) ) {
			return;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? stripslashes( $_SERVER['HTTP_USER_AGENT'] ) : '';
		if ( empty( $user_agent ) || preg_match( '/' . $this->bots . '/i', $user_agent ) ) {
			die( 'not tracking bots' );
		}
	}

	/**
	 * Processes each ad and inserts/updates tracking data.
	 *
	 * @return void
	 */
	private function process_tracking(): void {
		global $wpdb;

		@date_default_timezone_set( 'UTC' );

		$bid    = isset( $this->data['bid'] ) ? (int) $this->data['bid'] : 0;
		$prefix = $bid > 1 ? $wpdb->prefix . $bid . '_' : $wpdb->prefix;
		$table  = Constants::TRACK_IMPRESSION === $this->data['action'] ? Constants::TABLE_IMPRESSIONS : Constants::TABLE_CLICKS;
		$table  = $prefix . $table;

		$ads = array_count_values( $this->data['ads'] );

		foreach ( $ads as $ad_id => $count ) {
			$error_msg = $this->track_ad( $ad_id, $table, absint( $count ) );

			// 2: debugging active, 3: ad_id to debug.
			if ( '%2$s' === 'true' || (int) '%3$d' === $ad_id ) {
				while ( $count-- ) {
					Debugger::log(
						$ad_id,
						$table,
						empty( $error_msg ) ? round( ( microtime( true ) - $this->start_time ) * 1000 ) : -1,
						'Frontend on AMP' === $this->data['handler'] ? 'Frontend on AMP' : 'Frontend',
						$error_msg,
						'%1$s' // 1: debug file.
					);
				}
			}
		}

		die( 'OK' );
	}

	/**
	 * Inserts or updates tracking stats for a given ad ID.
	 *
	 * @param int    $ad_id      The ad ID to track.
	 * @param string $table_name Table name (clicks or impressions).
	 * @param int    $count      Number of counts to insert.
	 *
	 * @return string Error message or empty string on success.
	 */
	private function track_ad( int $ad_id, string $table_name, int $count ): string {
		global $wpdb;

		$timestamp = $this->generate_timestamp();

		// Use of %% is to avoid vsprintf to convert it to 0.
		$sql = $wpdb->prepare(
			"INSERT INTO $table_name (ad_id, timestamp, count) VALUES (%%d, %%d, %%d)
			ON DUPLICATE KEY UPDATE count = count + %%d",
			$ad_id,
			$timestamp,
			$count,
			$count
		);

		$result = $wpdb->query( $sql ); // phpcs:ignore
		return false !== $result ? '' : $wpdb->last_error;
	}

	/**
	 * Generates a tracking timestamp string in the expected format.
	 *
	 * @return string
	 */
	private function generate_timestamp(): string {
		static $timestamp;
		if ( ! is_null( $timestamp ) ) {
			return $timestamp;
		}

		$timezone = '%7$s';
		if ( preg_match( '/^\d/', $timezone ) ) {
			$timezone = '+' . $timezone;
		}
		$time = new DateTime( 'now', new DateTimeZone( $timezone ) );

		// Default timestamp.
		$timestamp = $time->format( 'ymWd06' );

		// Check for week/month inconsistencies.
		$week  = absint( $time->format( 'W' ) );
		$month = absint( $time->format( 'm' ) );

		if ( 52 <= $week && 1 === $month ) { // Still week 52 but already in January.
			$timestamp = $time->format( 'ym01d06' );
		} elseif ( 12 === $month && $week > 52 ) { // Still in December but week 53.
			$timestamp = $time->format( 'ym52d06' );
		}

		return $timestamp;
	}
}

( new AdvancedAds_Fast_Tracker() );
