<?php
/**
 * This class handles scheduled email reports and individual ad performance reporting.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Crons;

use Advanced_Ads_Utils;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Utilities\Email;
use AdvancedAds\Framework\Interfaces\Initializer_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Crons Emails.
 */
class Reporting_Emails implements Initializer_Interface {

	/**
	 * Run this initializer.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'advanced_ads_daily_email', [ $this, 'daily_email' ] );
		add_action( 'advanced_ads_daily_report', [ $this, 'individual_email_report' ] );
	}

	/**
	 *  Daily ( & weekly & monthly ) email function
	 *
	 * @return void
	 */
	public function daily_email(): void {
		$options = wp_advads_tracking()->options->get_all();
		if ( empty( $options ) ) {
			return;
		}

		if ( Helpers::is_tracking_method( 'ga' ) ) {
			$this->log_report_cron( 'full report: email reports are not working with Google Analytics' );

			return;
		}

		$sched = $options['email-sched'] ?? 'daily';
		$now   = date_create( 'now', Advanced_Ads_Utils::get_wp_timezone() );

		$this->log_report_cron( 'full report: schedule: ' . $sched );
		$this->log_report_cron( 'full report: current time: ' . print_r( $now, true ) );

		/**
		 *  Site admin reports
		 */
		$result = 'not sent';
		switch ( $sched ) {
			case 'monthly':
				if ( $now->format( 'd' ) === '01' ) {
					$result = Email::send_email_report();
					$this->log_report_cron( 'full report: schedule: ' . $sched );
				}
				break;

			case 'weekly':
				if ( $now->format( 'w' ) === '1' ) {
					$result = Email::send_email_report();
				}
				break;

			default: // Daily.
				$result = Email::send_email_report();
		}

		$this->log_report_cron( 'full report: send?: ' . print_r( $result, true ) );
	}

	/**
	 *  Individual ad email function
	 */
	public function individual_email_report() {
		global $woocommerce;

		if ( Helpers::is_tracking_method( 'ga' ) ) {
			$this->log_report_cron( 'email reports are not working with Google Analytics' );

			return;
		}

		$per_ad_reports = $this->get_ad_reports_params();

		$now = date_create( 'now', Advanced_Ads_Utils::get_wp_timezone() );

		foreach ( $per_ad_reports as $item ) {
			if ( 'never' === $item['frequency'] ) {
				continue;
			}
			$frequency   = $item['frequency'];
			$ad_id       = $item['id'];
			$period      = $item['period'];
			$recip       = $item['recip'];
			$period_name = $item['period-literal'];

			// If ad was sold via WooCommerce.
			$order_id = get_post_meta( $ad_id, 'advanced_ads_selling_order', true );
			if ( $order_id ) {
				$order     = \wc_get_order( $order_id );
				$is_latest = isset( $woocommerce->version ) && version_compare( $woocommerce->version, '3.0', '>=' );
				$recip     = $is_latest ? $order->get_billing_email() : $order->billing_email;
			}

			$debug_string = 'report for ad ID ' . $ad_id;

			if ( empty( $recip ) ) {
				$this->log_report_cron( $debug_string . ': recipient missing' );
				continue;
			}

			$this->log_report_cron( $debug_string . ': frequency: ' . $frequency );
			$this->log_report_cron( $debug_string . ': current time: ' . print_r( $now, true ) );

			/* translators: 1. statistics period 2. ad name */
			$subject = sprintf( __( 'Ad statistics for %1$s for %2$s', 'advanced-ads-tracking' ), $period_name, $item['title'] );
			$result  = 'not sent';

			// if the ad is expired, send one last report after expiration.
			if ( $this->ad_expired_report( (int) $ad_id, $frequency, $now->getTimestamp() ) ) {
				$this->log_report_cron( $debug_string . ': ad is expired and last report has already been sent.' );

				return;
			}

			switch ( $frequency ) {
				case 'monthly':
					// If start of month.
					if ( $now->format( 'd' ) === '01' ) {
						$result = Email::send_individual_ad_report(
							[
								'subject' => $subject,
								'to'      => $recip,
								'id'      => $ad_id,
								'period'  => $period,
							]
						);
					}
					break;

				case 'weekly':
					if ( $now->format( 'w' ) === '1' ) {
						// If monday.
						$result = Email::send_individual_ad_report(
							[
								'subject' => $subject,
								'to'      => $recip,
								'id'      => $ad_id,
								'period'  => $period,
							]
						);
					}
					break;

				default:
					$result = Email::send_individual_ad_report(
						[
							'subject' => $subject,
							'to'      => $recip,
							'id'      => $ad_id,
							'period'  => $period,
						]
					);
			}

			$this->log_report_cron( $debug_string . ': send?: ' . print_r( $result, true ) );
		}
	}

	/**
	 * Log scheduled reports if debugging constant `ADVANCED_ADS_TRACKING_CRON_DEBUG` is set in wp-config.php
	 *
	 * @param string $content Message that should be logged.
	 *
	 * @return void
	 */
	private function log_report_cron( $content ): void {
		if ( defined( 'ADVANCED_ADS_TRACKING_CRON_DEBUG' ) && ADVANCED_ADS_TRACKING_CRON_DEBUG ) {
			error_log( $content . "\n", 3, WP_CONTENT_DIR . '/advanced-ads-tracking-cron.csv' );
		}
	}

	/**
	 * Retrieve ad ids, period, frequency and report recipient for all ads.
	 *
	 * @return array
	 */
	private function get_ad_reports_params() {
		global $wpdb;

		$params = [];
		$query  = wp_advads_ad_query(
			[
				'post_status' => 'publish',
				'fields'      => 'ids',
				'meta_query'  => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'value'   => $wpdb->esc_like( 'report-frequency' ),
						'compare' => 'LIKE',
					],
				],
			]
		);
		$ad_ids = $query->have_posts() ? array_map( 'absint', $query->posts ) : [];

		$period_names = [
			'last30days'   => __( 'last 30 days', 'advanced-ads-tracking' ),
			'lastmonth'    => __( 'the last month', 'advanced-ads-tracking' ),
			'last12months' => __( 'last 12 months', 'advanced-ads-tracking' ),
		];

		foreach ( $ad_ids as $ad_id ) {
			$ad = wp_advads_get_ad( $ad_id );
			if ( ! $ad ) {
				continue;
			}

			$options = $ad->get_data();
			$options = $options['tracking'] ?? [];
			if ( isset( $options['report-frequency'] ) && 'never' !== $options['report-frequency'] ) {
				$params[ $ad_id ] = [
					'id'             => $ad_id,
					'frequency'      => $options['report-frequency'],
					'period'         => $options['report-period'],
					'recip'          => $ad->get_prop( 'tracking.report-recip' ),
					'title'          => $ad->get_title(),
					'period-literal' => $period_names[ $options['report-period'] ],
				];
			}
		}

		return $params;
	}

	/**
	 * Check if the last report for an expired ad has already been sent.
	 *
	 * @param int    $ad_id         The ad id.
	 * @param string $frequency     Email report frequency, `daily`, `weekly`, `monthly`.
	 * @param int    $now_timestamp The current timestamp.
	 *
	 * @return bool
	 */
	private function ad_expired_report( $ad_id, $frequency, $now_timestamp ) {
		$ad            = wp_advads_get_ad( $ad_id );
		$ad_expiration = $ad->get_expiry_date();
		if ( empty( $ad_expiration ) ) {
			return false;
		}
		$offset = [
			'daily'   => DAY_IN_SECONDS,
			'weekly'  => WEEK_IN_SECONDS,
			'monthly' => MONTH_IN_SECONDS,
		];
		if ( ! array_key_exists( $frequency, $offset ) ) {
			return false;
		}

		return $now_timestamp > $ad_expiration + $offset[ $frequency ];
	}
}
