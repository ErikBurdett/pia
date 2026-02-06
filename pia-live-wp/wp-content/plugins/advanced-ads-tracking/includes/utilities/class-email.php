<?php
/**
 * Utilities Email.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Utilities;

use DateTimeZone;
use Advanced_Ads_Utils;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Public_Ad;

defined( 'ABSPATH' ) || exit;

/**
 * Utilities Email.
 */
class Email {

	/**
	 * Send individual ad report
	 *
	 * @param array $params Array with email parameters, Keys subject, to, id, and period must exist.
	 *
	 * @return array array with email success and error if any.
	 */
	public static function send_individual_ad_report( array $params ) {
		if ( ! isset( $params['subject'], $params['to'], $params['id'], $params['period'] ) ) {
			return [
				'status' => false,
				'error'  => '',
			];
		}

		$bcc = explode( ',', $params['to'] );
		$to  = array_shift( $bcc );

		$options = wp_advads_tracking()->options->get_all();
		$sender  = $options['email-sender-name'] ?? 'Advanced Ads';
		$from    = $options['email-sender-address'] ?? 'noreply@' . wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $sender . ' <' . $from . '>',
		];
		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ',', $bcc );
		}

		ob_start();

		$content = self::get_email_report_content(
			[
				'period' => $params['period'],
				'ads'    => $params['id'],
			]
		);

		$result = wp_mail( $to, $params['subject'], $content, $headers );
		$error  = ob_get_clean();

		return [
			'status' => $result,
			'error'  => $error,
		];
	}

	/**
	 * Send ads reports to admin email
	 *
	 * @return array
	 */
	public static function send_email_report() {
		$options = wp_advads_tracking()->options->get_all();
		if ( empty( $options['email-addresses'] ) ) {
			return [
				'status' => false,
				'error'  => '',
			];
		}

		$period  = $options['email-stats-period'];
		$content = self::get_email_report_content(
			[
				'period'          => $period,
				'secondary_table' => true,
			]
		);

		if ( ! $content ) {
			return [
				'status' => false,
				'error'  => '',
			];
		}

		$bcc     = explode( ',', $options['email-addresses'] );
		$to      = array_shift( $bcc );
		$subject = $options['email-subject'];
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $options['email-sender-name'] . ' <' . $options['email-sender-address'] . '>',
		];

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ',', $bcc );
		}

		ob_start();
		$result = wp_mail( $to, $subject, $content, $headers );
		$error  = ob_get_clean();

		return [
			'status' => $result,
			'error'  => $error,
		];
	}

	/**
	 * Get aggregated stats by ad for an entire period
	 *
	 * @param array $impressions impressions count by date from `Advanced_Ads_Tracking_Admin::load_stats()`.
	 * @param array $clicks      clicks count by date from `Advanced_Ads_Tracking_Admin::load_stats()`.
	 *
	 * @return array
	 */
	public static function get_aggergated_stats_by_ad( $impressions, $clicks ) {
		$results = [];

		self::process_stats_data( $impressions, $results, 'impressions' );
		self::process_stats_data( $clicks, $results, 'clicks' );

		foreach ( $results as $id => &$stats ) {
			$impressions = (int) $stats['impressions'];
			$clicks      = (int) $stats['clicks'];

			$stats['ctr'] = $impressions > 0
				? number_format( ( 100 * $clicks ) / $impressions, 2 ) . '%'
				: '0.00%';

			// Add tracking status indicators (pass ID; utility resolves object internally).
			$stats['impression_tracking'] = \AdvancedAds\Tracking\Utilities\Tracking::has_ad_tracking_enabled( $id, 'impression', 'view' );
			$stats['click_tracking']      = \AdvancedAds\Tracking\Utilities\Tracking::has_ad_tracking_enabled( $id, 'click', 'view' );
		}

		return $results;
	}

	/**
	 * Create the email content of ads reports.
	 *
	 * @param string[] $report_args array with possible values for period and ad_id.
	 *
	 * @return string
	 */
	private static function get_email_report_content( $report_args = [] ) {
		$period = isset( $report_args['period'] ) ? $report_args['period'] : '';
		$ad_id  = isset( $report_args['ads'] ) ? $report_args['ads'] : 'all';

		if ( 'all' !== $ad_id ) {
			$ad_id = absint( $ad_id );
		}

		$valid_period = [ 'last30days', 'last12months', 'lastmonth' ];
		if ( ! in_array( $period, $valid_period, true ) ) {
			$period = 'last30days';
		}

		$textual_period = [
			'last30days'   => __( ' the last 30 days', 'advanced-ads-tracking' ),
			'lastmonth'    => __( ' the last month', 'advanced-ads-tracking' ),
			'last12months' => __( ' the last 12 months', 'advanced-ads-tracking' ),
		];

		$today = date_create( 'now', Advanced_Ads_Utils::get_wp_timezone() );
		$args  = [
			'ad_id'       => [],
			'period'      => 'lastmonth',
			'groupby'     => 'day',
			'groupFormat' => 'Y-m-d',
			'from'        => null,
			'to'          => null,
		];

		if ( 'last30days' === $period ) {
			$start_ts  = (int) $today->format( 'U' );
			$start_ts -= ( 30 * 24 * 60 * 60 );

			$start = date_create( '@' . $start_ts, new DateTimeZone( 'UTC' ) );

			$args['period'] = 'custom';
			$args['from']   = $start->format( 'm/d/Y' );

			$end_ts = (int) $today->format( 'U' );
			$end    = date_create( '@' . $end_ts, new DateTimeZone( 'UTC' ) );

			$args['to'] = $end->format( 'm/d/Y' );
		}

		if ( 'last12months' === $period ) {
			$start_ts  = (int) $today->format( 'U' );
			$start_ts -= ( 365 * 24 * 60 * 60 );

			$start = date_create( '@' . $start_ts, new DateTimeZone( 'UTC' ) );

			$args['period']  = 'custom';
			$args['groupby'] = 'month';
			$args['from']    = $start->format( 'm/' ) . '1' . $start->format( '/Y' );

			// Fix potential time zone gap.
			$end_ts  = (int) $today->format( 'U' );
			$end_ts += ( 24 * 60 * 60 );
			$end     = date_create( '@' . $end_ts, new DateTimeZone( 'UTC' ) );

			$args['to'] = $end->format( 'm/d/Y' );
		}

		$impr_stats  = Database::load_stats( $args, Database::get_impression_table() );
		$click_stats = Database::load_stats( $args, Database::get_click_table() );

		$ad_name      = false;
		$public_stats = false;

		/**
		 *  Filter ad ids to allow correct display if no stats for the corresponding ad
		 */
		if ( 'all' !== $ad_id ) {
			$__imprs  = [];
			$__clicks = [];
			foreach ( $impr_stats as $date => $impression ) {
				$key = (string) $ad_id;

				$__imprs[ $date ] = array_key_exists( $key, $impression )
					? [ $key => $impression[ $key ] ]
					: [ $key => 0 ];

				if ( isset( $click_stats[ $date ] ) ) {
					$__clicks[ $date ] = array_key_exists( $key, $click_stats[ $date ] )
						? [ $key => absint( $click_stats[ $date ][ $key ] ) ]
						: [ $key => 0 ];
				} else {
					$__clicks[ $date ] = [ $key => 0 ];
				}
			}

			$impr_stats   = $__imprs;
			$click_stats  = $__clicks;
			$public       = new Public_Ad( $ad_id );
			$ad_name      = $public->get_name( true );
			$public_stats = $public->get_url();
		}

		$cell_style   = 'padding: 0.6em;text-align:right;border:1px solid;';
		$header_style = 'padding: 0.8em;text-align:center;font-size:1.1em;font-weight:bold;';

		ob_start();
		include AA_TRACKING_ABSPATH . 'views/emails/email-report-body.php';
		return ob_get_clean();
	}

	/**
	 * Process stats data and aggregate by ad ID.
	 *
	 * @param array  $stats_data Array of stats data to process.
	 * @param array  $results    Results array to populate (passed by reference).
	 * @param string $field      Field name to increment ('impressions' or 'clicks').
	 */
	private static function process_stats_data( $stats_data, &$results, $field ) {
		foreach ( $stats_data as $stats ) {
			foreach ( $stats as $id => $count ) {
				$title = get_the_title( $id );
				if ( '' === $title || null === $title ) {
					continue;
				}

				if ( ! isset( $results[ $id ] ) ) {
					$results[ $id ] = [
						'name'        => $title,
						'impressions' => 0,
						'clicks'      => 0,
					];
				}
				$results[ $id ][ $field ] += (int) $count;
			}
		}
	}
}
