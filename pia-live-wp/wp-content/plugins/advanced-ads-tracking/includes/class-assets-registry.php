<?php
/**
 * Assets registry handles the registration of stylesheets and scripts required for plugin functionality.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use Advanced_Ads_Utils;
use AdvancedAds\Framework;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Tracking\Utilities\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Assets Registry.
 */
class Assets_Registry extends Framework\Assets_Registry {

	/**
	 * Base URL for plugin local assets.
	 *
	 * @return string
	 */
	public function get_base_url(): string {
		return AA_TRACKING_BASE_URL;
	}

	/**
	 * Prefix to use in handle to make it unique.
	 *
	 * @return string
	 */
	public function get_prefix(): string {
		return AA_TRACKING_SLUG;
	}

	/**
	 * Version for plugin local assets.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return AAT_VERSION;
	}

	/**
	 * Register styles
	 *
	 * @return void
	 */
	public function register_styles(): void {
		// Frontend.

		// Backend.
		$this->register_style( 'jquery-ui', 'assets/vendors/jquery-ui/jquery-ui.min.css', [], '1.11.4' );
		$this->register_style( 'wp-dashboard', 'assets/dist/wp-dashboard.css', [ $this->prefix_it( 'jquery-ui' ) ], $this->get_version() );
		$this->register_style( 'screen-ads-listing-tracking', 'assets/dist/screen-ads-listing.css', [], $this->get_version() );
		$this->register_style( 'admin-styles', 'assets/dist/admin.css', [], $this->get_version() );
		$this->register_style( 'jqplot', 'assets/vendors/jqplot/jquery.jqplot.min.css' );
		$this->register_style( 'datatables', 'assets/vendors/datatables/css/datatables.min.css' );
		$this->register_style( 'screen-settings', 'assets/dist/settings.css', [], AAT_VERSION );
		$this->register_style( 'public-stats', 'assets/dist/public-stats.css', [], AAT_VERSION );
	}

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		// Frontend.
		$src  = 'assets/dist/public-stats.js';
		$deps = [
			'jquery',
			'jquery-ui-datepicker',
			$this->prefix_it( 'jqplot-date' ),
			$this->prefix_it( 'jqplot-highlighter' ),
			$this->prefix_it( 'jqplot-cursor' ),
		];
		$this->register_script( 'frontend-public-stats', $src, $deps, $this->get_version(), true );

		// Backend.
		$this->register_script( 'wp-dashboard', 'assets/js/admin/wp-dashboard.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->get_version(), true );
		$this->register_script( 'screen-ads-listing-tracking', 'assets/js/admin/screen-ads-listing.js', [], $this->get_version() );
		$this->register_script( 'jqplot', 'assets/vendors/jqplot/jquery.jqplot.min.js', [ 'jquery' ], false, true );
		$this->register_script( 'jqplot-date', 'assets/vendors/jqplot/plugins/jqplot.dateAxisRenderer.min.js', [ $this->prefix_it( 'jqplot' ) ], false, true );
		$this->register_script( 'jqplot-highlighter', 'assets/vendors/jqplot/plugins/jqplot.highlighter.min.js', [ $this->prefix_it( 'jqplot' ) ], false, true );
		$this->register_script( 'jqplot-canvas-axis-label-renderer', 'assets/vendors/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js', [ $this->prefix_it( 'jqplot' ) ], false, true );
		$this->register_script( 'jqplot-canvas-text-renderer', 'assets/vendors/jqplot/plugins/jqplot.canvasTextRenderer.min.js', [ $this->prefix_it( 'jqplot' ) ], false, true );
		$this->register_script( 'jqplot-cursor', 'assets/vendors/jqplot/plugins/jqplot.cursor.min.js', [ $this->prefix_it( 'jqplot' ) ], false, true );
		$this->register_script( 'datatables', 'assets/vendors/datatables/js/datatables.min.js', [ 'jquery' ], false, true );
		$this->register_script( 'date-format', 'assets/vendors/date.format/date.format.min.js', [ 'jquery' ], false, true );
		$this->register_script( 'media-frame', 'assets/js/wp-media-frame.js', [ 'jquery' ], false, true );
		$this->register_script( 'period-select', 'assets/js/period-select.js', [ 'jquery', 'jquery-ui-datepicker' ], false, true );
		$this->register_script( 'screen-stats', 'assets/js/admin-stats.js', [], false, true );
		$this->register_script( 'screen-database', 'assets/js/db-operations.js', [ $this->prefix_it( 'period-select' ), 'wp-util' ], '1.25.0', true );
		$this->register_script( 'screen-settings', 'assets/js/settings.js', [ 'jquery' ], false, true );

		$deps = [
			$this->prefix_it( 'jqplot-date' ),
			$this->prefix_it( 'jqplot-highlighter' ),
			$this->prefix_it( 'jqplot-canvas-axis-label-renderer' ),
			$this->prefix_it( 'jqplot-canvas-text-renderer' ),
			$this->prefix_it( 'jqplot-cursor' ),
			$this->prefix_it( 'datatables' ),
			$this->prefix_it( 'date-format' ),
			$this->prefix_it( 'media-frame' ),
			$this->prefix_it( 'period-select' ),
			$this->prefix_it( 'screen-stats' ),
		];
		$this->register_script( 'screen-stats-file', 'assets/js/stats-from-file.js', $deps, null, true );

		$deps = [
			$this->prefix_it( 'jqplot-date' ),
			$this->prefix_it( 'jqplot-highlighter' ),
			$this->prefix_it( 'jqplot-canvas-axis-label-renderer' ),
			$this->prefix_it( 'jqplot-canvas-text-renderer' ),
			$this->prefix_it( 'jqplot-cursor' ),
		];
		$this->register_script( 'ads-editing-stats', 'assets/js/ad-editing-stats.js', $deps, null, true );

		$this->localize_data();

		if ( Conditional::is_screen( 'dashboard' ) ) {
			$pref = get_user_meta( get_current_user_id(), 'advads_tracking_performing_ads_prefs', true ) ?: []; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			wp_advads_json_add(
				'dashboardAdsWidget',
				[
					'period'     => $pref['period'] ?? 'last7days',
					'metric'     => $pref['metric'] ?? 'clicks',
					'customFrom' => $pref['custom_from'] ?? '',
					'customTo'   => $pref['custom_to'] ?? '',
				]
			);
		}
	}

	/**
	 * Enqueue localize data
	 *
	 * phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
	 *
	 * @return void
	 */
	private function localize_data(): void {
		$media_locale = [
			'selectFile'      => esc_attr__( 'Select file', 'advanced-ads-tracking' ),
			'button'          => advads__( 'select' ),
			'invalidFileType' => esc_attr__( 'invalid file type', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'media-frame' ), 'advadsMediaFrameLocale', $media_locale );

		$stats_translations = [
			'clicks'              => esc_attr__( 'clicks', 'advanced-ads-tracking' ),
			/* translators: %s: ad name */
			'clicksFor'           => esc_attr__( 'clicks for "%s"', 'advanced-ads-tracking' ),
			'Clicks'              => esc_attr__( 'Clicks', 'advanced-ads-tracking' ),
			'impressions'         => esc_attr__( 'impressions', 'advanced-ads-tracking' ),
			/* translators: %s: ad name */
			'impressionsFor'      => esc_attr__( 'impressions for "%s"', 'advanced-ads-tracking' ),
			'Impressions'         => esc_attr__( 'Impressions', 'advanced-ads-tracking' ),
			'prevDay'             => esc_attr__( 'previous day', 'advanced-ads-tracking' ),
			'nextDay'             => esc_attr__( 'next day', 'advanced-ads-tracking' ),
			'prevMonth'           => esc_attr__( 'previous month', 'advanced-ads-tracking' ),
			'nextMonth'           => esc_attr__( 'next month', 'advanced-ads-tracking' ),
			'prevYear'            => esc_attr__( 'previous year', 'advanced-ads-tracking' ),
			'nextYear'            => esc_attr__( 'next year', 'advanced-ads-tracking' ),
			/* translators: %d: number of days */
			'prev%dDays'          => esc_attr__( 'previous %d days', 'advanced-ads-tracking' ),
			/* translators: %d: number of days */
			'next%dDays'          => esc_attr__( 'next %d days', 'advanced-ads-tracking' ),
			/* translators: %1$s: from date, %2$s: to date */
			'clicksFromTo'        => esc_attr__( 'clicks from %1$s to %2$s', 'advanced-ads-tracking' ),
			/* translators: %1$s: from date, %2$s: to date */
			'imprFromTo'          => esc_attr__( 'impressions from %1$s to %2$s', 'advanced-ads-tracking' ),
			/* translators: %1$s: from date, %2$s: to date */
			'noDataFor'           => esc_attr__( 'There is no data for %1$s to %2$s', 'advanced-ads-tracking' ),
			'ad'                  => esc_attr__( 'ad', 'advanced-ads-tracking' ),
			'ctr'                 => esc_attr__( 'CTR', 'advanced-ads-tracking' ),
			'deletedAds'          => esc_attr__( 'deleted ads', 'advanced-ads-tracking' ),
			'date'                => esc_attr__( 'date', 'advanced-ads-tracking' ),
			/* translators: %1$s: from date, %2$s: to date */
			'aTob'                => esc_attr__( '%1$s to %2$s', 'advanced-ads-tracking' ),
			'total'               => esc_attr__( 'total', 'advanced-ads-tracking' ),
			'noRecords'           => esc_attr__( 'There is no record for this period :(', 'advanced-ads-tracking' ),
			'periodNotConsistent' => esc_attr__( 'The period you have chosen is not consistent', 'advanced-ads-tracking' ),
			'customPeriodMissing' => esc_attr__( 'Some fields are missing for the custom period', 'advanced-ads-tracking' ),
			'invalidRecord'       => esc_attr__( 'One or more invalid records have been found in the database', 'advanced-ads-tracking' ),
			'noFile'              => esc_attr__( 'no file selected', 'advanced-ads-tracking' ),
			'group'               => esc_attr__( 'group', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'screen-stats' ), 'statsLocale', $stats_translations );

		$gmt_offset = 3600 * 1000 * (float) get_option( 'gmt_offset' );
		$date       = [
			'WPGmtOffset'        => $gmt_offset,
			'wpDateFormat'       => str_replace( '\\', '\\\\', get_option( 'date_format', 'Y/m/d' ) ),
			'wpDateTimeZoneName' => esc_html( Advanced_Ads_Utils::get_timezone_name() ),
		];
		wp_localize_script( $this->prefix_it( 'screen-stats' ), 'statsDates', $date );

		$date_names = [
			'shortMonths' => [
				advads__( 'Jan' ),
				advads__( 'Feb' ),
				advads__( 'Mar' ),
				advads__( 'Apr' ),
				advads_x( 'May', 'May abbreviation' ),
				advads__( 'Jun' ),
				advads__( 'Jul' ),
				advads__( 'Aug' ),
				advads__( 'Sep' ),
				advads__( 'Oct' ),
				advads__( 'Nov' ),
				advads__( 'Dec' ),
			],
			'longMonths'  => [
				advads__( 'January' ),
				advads__( 'February' ),
				advads__( 'March' ),
				advads__( 'April' ),
				advads__( 'May' ),
				advads__( 'June' ),
				advads__( 'July' ),
				advads__( 'August' ),
				advads__( 'September' ),
				advads__( 'October' ),
				advads__( 'November' ),
				advads__( 'December' ),
			],
			'shortDays'   => [
				advads__( 'Sun' ),
				advads__( 'Mon' ),
				advads__( 'Tue' ),
				advads__( 'Wed' ),
				advads__( 'Thu' ),
				advads__( 'Fri' ),
				advads__( 'Sat' ),
			],
			'longDays'    => [
				advads__( 'Sunday' ),
				advads__( 'Monday' ),
				advads__( 'Tuesday' ),
				advads__( 'Wednesday' ),
				advads__( 'Thursday' ),
				advads__( 'Friday' ),
				advads__( 'Saturday' ),
			],
		];
		wp_localize_script( $this->prefix_it( 'screen-stats' ), '_dateName', $date_names );

		$data_table_locale = [
			'processing'     => esc_attr__( 'processing...', 'advanced-ads-tracking' ),
			'search'         => esc_attr__( 'search:', 'advanced-ads-tracking' ),
			'lengthMenu'     => esc_attr__( 'show _MENU_ entries', 'advanced-ads-tracking' ),
			'info'           => esc_attr__( 'showing _START_ to _END_ of _TOTAL_ entries', 'advanced-ads-tracking' ),
			'infoEmpty'      => esc_attr__( 'no element to show', 'advanced-ads-tracking' ),
			'infoFiltered'   => esc_attr__( 'filtered from _MAX_ total entries', 'advanced-ads-tracking' ),
			'infoPostFix'    => '',
			'loadingRecords' => esc_attr__( 'Loading...', 'advanced-ads-tracking' ),
			'zeroRecords'    => esc_attr__( 'no matching records found', 'advanced-ads-tracking' ),
			'emptyTable'     => esc_attr__( 'no data available in table', 'advanced-ads-tracking' ),
			'paginate'       => [
				'first'    => esc_attr__( 'first', 'advanced-ads-tracking' ),
				'previous' => esc_attr__( 'previous', 'advanced-ads-tracking' ),
				'next'     => esc_attr__( 'next', 'advanced-ads-tracking' ),
				'last'     => esc_attr__( 'last', 'advanced-ads-tracking' ),
			],
			'aria'           => [
				'sortAscending'  => esc_attr__( ': activate to sort column ascending', 'advanced-ads-tracking' ),
				'sortDescending' => esc_attr__( ': activate to sort column descending', 'advanced-ads-tracking' ),
			],
		];
		wp_localize_script( $this->prefix_it( 'screen-stats' ), '_dataTableLang', $data_table_locale );

		$stats_file_locale = [
			'unknownError'        => esc_attr__( 'An unexpected error occurred.', 'advanced-ads-tracking' ),
			/* translators: %1$s: from date, %2$s: to date */
			'statsFrom'           => esc_attr__( 'stats from %1$s to %2$s', 'advanced-ads-tracking' ),
			'periodNotConsistent' => esc_attr__( 'The period you have chosen is not consistent', 'advanced-ads-tracking' ),
			'statsNotFoundInFile' => __( 'No stats found in file', 'advanced-ads-tracking' ),
			/* translators: %d: number of days */
			'prev%dDays'          => esc_attr__( 'previous %d days', 'advanced-ads-tracking' ),
			/* translators: %d: number of days */
			'next%dDays'          => esc_attr__( 'next %d days', 'advanced-ads-tracking' ),
			'prevMonth'           => esc_attr__( 'previous month', 'advanced-ads-tracking' ),
			'nextMonth'           => esc_attr__( 'next month', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'screen-stats-file' ), 'statsFileLocale', $stats_file_locale );

		$dbop_locale = [
			'serverFail'      => esc_attr__( 'The server failed to respond to your request.', 'advanced-ads-tracking' ),
			'unknownError'    => esc_attr__( 'An unexpected error occurred.', 'advanced-ads-tracking' ),
			'resetNoAd'       => esc_attr__( 'Please choose an ad', 'advanced-ads-tracking' ),
			'resetConfirm'    => esc_attr__( 'Are you sure you want to reset the stats for', 'advanced-ads-tracking' ),
			'SQLFailure'      => esc_attr__( 'The plugin was not able to perform some requests on the database', 'advanced-ads-tracking' ),
			'optimizeFailure' => esc_attr__( 'Data were compressed but the tracking tables can not be optimized automatically. Please ask the server&#39;s admin on how to proceed.', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'screen-database' ), 'trackingDbopLocale', $dbop_locale );

		$tracking_locale = [
			'serverFail'    => esc_attr__( 'The server failed to respond to your request. Link structure not available.', 'advanced-ads-tracking' ),
			'unknownError'  => esc_attr__( 'An unexpected error occurred. Link structure not available.', 'advanced-ads-tracking' ),
			'linkAvailable' => esc_attr__( 'Link structure available.', 'advanced-ads-tracking' ),
			'emailSent'     => esc_attr__( 'email sent', 'advanced-ads-tracking' ),
			'emailNotSent'  => esc_attr__( 'email not sent. Please check your server configuration', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'screen-settings' ), 'trackingSettingsLocale', $tracking_locale );

		$tracking_stats_locale = [
			'impressions' => __( 'impressions', 'advanced-ads-tracking' ),
			'clicks'      => __( 'clicks', 'advanced-ads-tracking' ),
		];
		wp_localize_script( $this->prefix_it( 'ads-editing-stats' ), 'advadsStatsLocale', $tracking_stats_locale );
		wp_localize_script( $this->prefix_it( 'ads-editing-stats' ), 'advads_tracking_clickable_ad_types', Data::get_clickable_types() );
	}
}
