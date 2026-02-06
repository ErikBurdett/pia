<?php
/**
 * Cron Jobs.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Crons;

use DateTime;
use Exception;
use DateTimeZone;
use Advanced_Ads_Utils;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Cron Jobs.
 */
class Jobs implements Integration_Interface {

	/**
	 * Cron jobs.
	 *
	 * @var array
	 */
	const CRONJOBS = [
		'advanced_ads_daily_email',
		'advanced_ads_daily_report',
	];

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		$this->schedule_jobs();
		add_filter( 'pre_update_option_timezone_string', [ $this, 'schedule_jobs_on_timezone_changed' ], 50, 2 );
		add_filter( 'pre_update_option_gmt_offset', [ $this, 'schedule_jobs_on_timezone_changed' ], 10, 2 );
	}

	/**
	 * Schedule cron jobs.
	 *
	 * @return void
	 */
	public function schedule_jobs(): void {
		try {
			$schedule_time = ( new DateTime( 'tomorrow 00:15', Advanced_Ads_Utils::get_wp_timezone() ) )->getTimestamp();
		} catch ( Exception $e ) {
			return;
		}

		foreach ( self::CRONJOBS as $cron ) {
			$scheduled = wp_get_schedule( $cron );
			// if there is a registered cron job, but ga is the chosen tracking method, remove cron job.
			if ( $scheduled && Helpers::is_tracking_method( 'ga' ) ) {
				wp_clear_scheduled_hook( $cron );
				continue;
			}

			// if there is no cron job, register the cron job.
			if ( ! $scheduled ) {
				wp_schedule_event( $schedule_time, 'daily', $cron );
			}
		}
	}

	/**
	 * Reschedule cron job if time zone updated in WP.
	 *
	 * @param string $new_tz New timezone or GMT offset string.
	 * @param string $old_tz Old timezone or GMT offset string.
	 *
	 * @return string
	 */
	public function schedule_jobs_on_timezone_changed( $new_tz, $old_tz ): string {
		// Early bail!!
		if ( $new_tz === $old_tz || empty( $new_tz ) ) {
			return $new_tz;
		}

		$timezone = $new_tz;
		if ( preg_match( '/^\d/', $new_tz ) ) {
			$timezone = '+' . $new_tz;
		}

		try {
			$schedule_time = ( new DateTime( 'tomorrow 00:15', new DateTimeZone( $timezone ) ) )->format( 'U' );
		} catch ( Exception $e ) {
			return $new_tz;
		}

		foreach ( self::CRONJOBS as $cron ) {
			if ( wp_get_schedule( $cron ) ) {
				wp_clear_scheduled_hook( $cron );
				wp_schedule_event( $schedule_time, 'daily', $cron );
			}
		}

		return $new_tz;
	}
}
