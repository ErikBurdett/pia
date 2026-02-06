<?php
/**
 * Ajax.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use WP_Query;
use DateTimeImmutable;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Utilities\Email;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax.
 */
class Ajax implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_ajax_' . Constants::TRACK_IMPRESSION, [ $this, 'track' ] );
		add_action( 'wp_ajax_' . Constants::TRACK_CLICK, [ $this, 'track' ] );
		add_action( 'wp_ajax_nopriv_' . Constants::TRACK_IMPRESSION, [ $this, 'track' ] );
		add_action( 'wp_ajax_nopriv_' . Constants::TRACK_CLICK, [ $this, 'track' ] );

		add_action( 'wp_ajax_advads-tracking-check-slug', [ $this, 'check_slug' ] );
		add_action( 'wp_ajax_advads-tracking-immediate-report', [ $this, 'immedate_report' ] );
		add_action( 'wp_ajax_advads_load_stats', [ $this, 'load_stats' ] );
		add_action( 'wp_ajax_advads_load_stats_file', [ $this, 'load_stats_file' ] );
		add_action( 'wp_ajax_advads_stats_file_info', [ $this, 'get_stats_file_info' ] );
		add_action( 'wp_ajax_advads_render_dashboard_ads_widget', [ $this, 'render_dashboard_ads_widget' ] );
		add_action( 'wp_ajax_advads_dashboard_ads_widget_user_prefs', [ $this, 'dashboard_ads_widget_user_prefs' ] );
		add_filter( 'advanced-ads-cache-busting-item', [ $this, 'enable_analytics' ] );
	}

	/**
	 * Enable tracking for ajax ads tracked with Google Analytics
	 *
	 * @param array $response ajax cache busting response.
	 *
	 * @return array
	 */
	public function enable_analytics( $response ): array {
		$method = wp_advads_tracking()->options->get( 'method', 'true' );

		if (
			( ! Helpers::is_forced_analytics() && 'ga' !== $method )
			|| 'advads_ad_select' !== Params::post( 'action' )
			|| empty( $response['ads'] )
		) {
			return $response;
		}

		foreach ( $response['ads'] as $id => $ad ) {
			if ( 'ad' !== $ad['type'] ) {
				continue;
			}
			$response['ads'][ $id ]['tracking_enabled'] = Tracking::has_ad_tracking_enabled( wp_advads_get_ad( (int) $ad['id'] ) );
		}

		return $response;
	}

	/**
	 * Retrieves statistics file information based on the provided CSV data.
	 *
	 * @return void
	 */
	public function get_stats_file_info() {
		check_ajax_referer( 'advads-stats-page', 'nonce' );

		$data   = $this->parse_csv( Params::post( 'id', 0, FILTER_VALIDATE_INT ) );
		$result = [
			'status' => false,
		];

		if ( isset( $data['firstdate'] ) ) {
			$result = [
				'status'    => true,
				'firstdate' => $data['firstdate'],
				'lastdate'  => $data['lastdate'],
				'ads'       => implode( '-', array_keys( $data['ads'] ) ),
			];
		}

		wp_send_json( $result );
	}

	/**
	 *  Load stats from file for a given period
	 *
	 * @return void
	 */
	public function load_stats_file(): void {
		check_ajax_referer( 'advads-stats-page', 'nonce' );

		parse_str( Params::post( 'args' ), $args );

		$result = [ 'status' => false ];
		$data   = $this->parse_csv( (int) $args['file'] );
		if ( isset( $data['status'] ) && $data['status'] ) {
			$result = $this->prepare_stats_from_file( $data, $args['period'], $args['from'], $args['to'], $args['groupby'] );
		}

		wp_send_json( $result );
	}

	/**
	 *  Load stats for a given period
	 *
	 * @return void
	 */
	public function load_stats(): void {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		$result = $this->load_stats_from_db();

		wp_send_json( $result );
	}

	/**
	 *  Send immediately an email report
	 *
	 * @return void
	 */
	public function immedate_report(): void {
		check_ajax_referer( 'advads-tracking-public-stats', 'nonce' );

		$result = Email::send_email_report();

		wp_send_json( $result );
	}

	/**
	 * Check if a slug is taken
	 *
	 * @return void
	 */
	public function check_slug(): void {
		check_ajax_referer( 'advads-tracking-public-stats', 'nonce' );

		$result = [ 'status' => false ];
		$title  = Params::post( 'title', false );
		$title  = $title ? stripslashes( $title ) : false;

		if ( $title ) {
			$to_slug = sanitize_title( $title );

			$category = get_term_by( 'slug', $to_slug, 'category' );
			$tag      = get_term_by( 'slug', $to_slug, 'post_tag' );
			$link     = get_term_by( 'slug', $to_slug, 'link_category' );
			$posts    = new WP_Query(
				[
					'post_type' => 'any',
					'name'      => $to_slug,
				]
			);

			if ( $posts->have_posts() ) {
				$result['msg'] = __( 'This base name collides with an existing WordPress content (blog post, page or any public custom content)', 'advanced-ads-tracking' );
			} elseif ( false !== $link ) {
				$result['msg'] = __( 'This base name collides with an existing link category', 'advanced-ads-tracking' );
			} elseif ( false !== $tag ) {
				$result['msg'] = __( 'This base name collides with an existing blog post tag', 'advanced-ads-tracking' );
			} elseif ( false !== $category ) {
				$result['msg'] = __( 'This base name collides with an existing blog post category', 'advanced-ads-tracking' );
			} else {
				// all clear.
				$result['status'] = true;
			}
			$result['slug']  = $to_slug;
			$result['title'] = $title;
		}

		wp_send_json( $result );
	}

	/**
	 * Track impressions.
	 *
	 * @return void
	 */
	public function track(): void {
		$start_time = microtime( true );

		// Do not stop when user ended the connection.
		ignore_user_abort( true );

		// Do nothing if called without payload.
		$ads = Params::request( 'ads', false, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( empty( $ads ) ) { // phpcs:ignore
			die( 'nothing to track' );
		}

		$ad_ids = array_filter( array_map( 'absint', $ads ) );

		if ( empty( $ads ) ) {
			die( 'nothing to track' );
		}

		$action = sanitize_text_field( Params::request( 'action' ) );

		if ( Constants::TRACK_CLICK === $action ) {
			foreach ( $ad_ids as $ad_id ) {
				Tracking::track_click( $ad_id, $start_time );
			}

			return;
		}

		if ( Constants::TRACK_IMPRESSION === $action ) {
			Tracking::track_impressions( $ad_ids, $start_time );
		}
	}

	/**
	 * Renders the performing ads widget on the WordPress dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard_ads_widget(): void {
		$stats = $this->load_stats_from_db();
		$stats = $stats['stats'] ?? [];
		$this->sum_and_prepare_performing_ads( $stats );

		ob_start();
		include_once AA_TRACKING_ABSPATH . 'views/admin/widgets/wordpress-dashboard/performing-ads.php';
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		die();
	}

	/**
	 * Get / Set performing ads user preferences.
	 *
	 * @return void
	 */
	public function dashboard_ads_widget_user_prefs(): void {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		$pref['period']      = Params::post( 'period', '' );
		$pref['metric']      = Params::post( 'metric', '' );
		$pref['custom_from'] = Params::post( 'custom_from', '' );
		$pref['custom_to']   = Params::post( 'custom_to', '' );

		update_user_meta( get_current_user_id(), 'advads_tracking_performing_ads_prefs', $pref );
		die();
	}

	/**
	 * Loads statistics from the database based on the provided arguments.
	 *
	 * @return array The loaded statistics and their status.
	 */
	private function load_stats_from_db(): array {
		$result = [ 'status' => false ];
		parse_str( Params::post( 'args' ), $args );

		if ( ! empty( $args['period'] ) ) {
			$result['status'] = true;
			$result['stats']  = [];

			/**
			 *  Prepare all locale dependant and groupby dependant variables needed jqplot and datatable
			 */
			$date_format  = 'Y-m-d';
			$group_format = 'Y-m-d';

			$groupby  = $args['groupby'];
			$groupbys = [
				// group format, axis label, value conversion for graph.
				'day'   => [ 'Y-m-d', __( 'day', 'advanced-ads-tracking' ), _x( 'Y-m-d', 'date format on stats page', 'advanced-ads-tracking' ) ],
				'week'  => [ 'o-\WW', __( 'week', 'advanced-ads-tracking' ), _x( 'Y-m-d', 'date format on stats page', 'advanced-ads-tracking' ) ],
				'month' => [ 'Y-m', __( 'month', 'advanced-ads-tracking' ), _x( 'Y-m', 'date format on stats page', 'advanced-ads-tracking' ) ],
			];

			if ( ! isset( $groupbys[ $groupby ] ) ) {
				$groupby = null;
			} else {
				$group_format = $groupbys[ $groupby ][0];
				$date_format  = $groupbys[ $groupby ][2];
			}

			/**
			 *  Load result from DB
			 */
			$ad_ids = Params::post( 'ads', '' );

			if ( empty( $ad_ids ) ) {
				$all_ads = wp_advads_get_ad_repository()->query(
					[ 'post_status' => [ 'publish', 'future', 'draft', 'advanced_ads_expired' ] ],
					true
				);
				$ad_ids  = $all_ads->have_posts() ? wp_list_pluck( $all_ads->posts, 'ID' ) : [];
			} else {
				$ad_ids = explode( '-', $ad_ids );
			}

			$sql_args = [
				'period'      => $args['period'],
				'groupby'     => $args['groupby'],
				'ad_id'       => $ad_ids,
				'groupFormat' => $group_format,
			];

			if ( 'custom' === $args['period'] ) {
				$sql_args['from'] = $args['from'];
				$sql_args['to']   = $args['to'];
			}

			$impr   = Database::load_stats( $sql_args, Database::get_impression_table() );
			$clicks = Database::load_stats( $sql_args, Database::get_click_table() );
			if ( ! is_array( $clicks ) ) {
				$clicks = [];
			}
			$firstdate = '';

			if ( $impr || $clicks ) {
				// If clicks only are present, fill impressions with zero.
				foreach ( $clicks as $date => $_stats ) {
					foreach ( $_stats as $key => $value ) {
						if ( ! isset( $impr[ $date ][ $key ] ) ) {
							$impr[ $date ][ $key ] = 0;
						}
					}
				}

				$result['stats']['click'] = $clicks;
				$result['stats']['impr']  = $impr;

				$time  = time();
				$today = date_create( '@' . $time );

				/**
				 *  Get the real start of period, in case it is anterior to the first stat found in order to keep stats length in comparison
				 */
				switch ( $sql_args['period'] ) {
					case 'custom':
						$result['stats']['periodStart'] = $sql_args['from'];
						$result['stats']['periodEnd']   = $sql_args['to'];
						break;

					case 'today':
						$result['stats']['periodStart'] = get_date_from_gmt( $today->format( 'Y-m-d H:i:s' ), 'Y-m-d' );
						$result['stats']['periodEnd']   = $result['stats']['periodStart'];
						break;

					case 'yesterday':
						$yesterday                      = date_create( '@' . ( $time - ( 24 * 3600 ) ) );
						$result['stats']['periodStart'] = get_date_from_gmt( $yesterday->format( 'Y-m-d H:i:s' ), 'Y-m-d' );
						$result['stats']['periodEnd']   = $result['stats']['periodStart'];
						break;

					case 'lastmonth':
						/**
						 *  Get next month start without using DateInterval for PHP 5.2
						 */
						$year                           = (int) $today->format( 'Y' );
						$month                          = (int) $today->format( 'm' );
						$decr_year                      = ( 1 > $month - 1 ) ? 1 : 0;
						$last_month                     = ( 1 > $month - 1 ) ? 12 - $month - 1 : $month - 1;
						$days_count                     = (int) DateTimeImmutable::createFromFormat( 'Y-n-j', sprintf( '%d-%d-1', ( $year - $decr_year ), $last_month ) )->format( 't' );
						$result['stats']['periodStart'] = get_date_from_gmt( ( $year - $decr_year ) . '-' . $last_month . '-1 ' . $today->format( 'H:i:s' ), 'Y-m-d' );
						$result['stats']['periodEnd']   = get_date_from_gmt( ( $year - $decr_year ) . '-' . $last_month . '-' . $days_count . ' ' . $today->format( 'H:i:s' ), 'Y-m-d' );
						break;

					case 'thismonth':
						$result['stats']['periodStart'] = get_date_from_gmt( $today->format( 'Y-m-1 H:i:s' ), 'Y-m-d' );
						/**
						 *  Get next month start without using DateInterval for PHP 5.2
						 */
						$days_count                   = (int) $today->format( 't' );
						$result['stats']['periodEnd'] = get_date_from_gmt( $today->format( 'Y-m-' . $days_count . ' H:i:s' ), 'Y-m-d' );
						break;

					case 'thisyear':
						$result['stats']['periodStart'] = get_date_from_gmt( $today->format( 'Y-1-1 H:i:s' ), 'Y-m-d' );
						$result['stats']['periodEnd']   = get_date_from_gmt( $today->format( 'Y-12-31 H:i:s' ), 'Y-m-d' );
						break;

					case 'lastyear':
						$result['stats']['periodEnd']   = get_date_from_gmt( ( (int) $today->format( 'Y' ) - 1 ) . $today->format( '-12-31 H:i:s' ), 'Y-m-d' );
						$result['stats']['periodStart'] = get_date_from_gmt( ( (int) $today->format( 'Y' ) - 1 ) . $today->format( '-01-01 H:i:s' ), 'Y-m-d' );
						break;

					// Last 7 days.
					default:
						$last7days                      = $time - ( 7 * 24 * 3600 );
						$d_last7days                    = date_create( '@' . $last7days );
						$yesterday                      = date_create( '@' . ( $time - ( 24 * 3600 ) ) );
						$result['stats']['periodStart'] = get_date_from_gmt( $d_last7days->format( 'Y-m-d H:i:s' ), 'Y-m-d' );
						$result['stats']['periodEnd']   = get_date_from_gmt( $yesterday->format( 'Y-m-d H:i:s' ), 'Y-m-d' );
				}
			}
			/**
			 *  Prepare jqplot and datatable variables that depend on date of first record ( if any record is found )
			 */
			if ( $impr ) {
				$formatstring = '%b&nbsp;%#d';
				reset( $impr );
				$firstdate = key( $impr );
				switch ( $args['groupby'] ) {
					case 'week':
						/* translators: %b: month, %#d: day */
						$formatstring = _x( 'from %b&nbsp;%#d', 'format for week group in stats table', 'advanced-ads-tracking' );
						$firstdate    = date( 'Y-m-d', strtotime( $firstdate . ' -1 week' ) ); // phpcs:ignore
						break;
					case 'month':
						$formatstring = '%B';
						$firstdate    = '';
						break;
					default:
						$firstdate = date( 'Y-m-d', strtotime( $firstdate . ' -1 day' ) ); // phpcs:ignore
				}

				$result['stats']['xAxisThickformat'] = $formatstring;
				$result['stats']['firstDate']        = $firstdate;
			}
		}

		// An invalid date has been found in the records.
		if ( ! empty( $firstdate ) && (int) str_replace( '-', '', $firstdate ) < 20100101 ) {
			$result = [
				'status' => false,
				'msg'    => 'invalid-record',
			];
		}

		return $result;
	}

	/**
	 * Sum and prepare stats data for rendering.
	 *
	 * @param array $stats Stats array passed by reference.
	 *
	 * @return void
	 */
	private function sum_and_prepare_performing_ads( array &$stats ): void {
		$clicks      = isset( $stats['click'] ) ? $this->sum_metric( $stats['click'] ) : [];
		$impressions = isset( $stats['impr'] ) ? $this->sum_metric( $stats['impr'] ) : [];

		arsort( $clicks, SORT_NUMERIC );
		arsort( $impressions, SORT_NUMERIC );

		$stats['clicks']      = array_slice( $clicks, 0, 5, true );
		$stats['impressions'] = array_slice( $impressions, 0, 5, true );

		$stats['ctr'] = [];
		if ( ! empty( $clicks ) && ! empty( $impressions ) ) {
			foreach ( $clicks as $ad_id => $click ) {
				$impression             = $impressions[ $ad_id ] ?? 0;
				$ctr                    = $impression > 0 ? number_format_i18n( $click / $impression * 100, 2 ) . '%' : '0%';
				$stats['ctr'][ $ad_id ] = $ctr;
			}
			arsort( $stats['ctr'], SORT_NUMERIC );
			$stats['ctr'] = array_slice( $stats['ctr'], 0, 5, true );
		}
	}

	/**
	 * Sum metric data.
	 *
	 * @param array $metric_data Clicks/Impressions data.
	 *
	 * @return array
	 */
	private function sum_metric( array $metric_data ): array {
		return array_reduce(
			$metric_data,
			function ( $acc, $group ) {
				foreach ( $group as $key => $value ) {
					$acc[ $key ] = ( isset( $acc[ $key ] ) ? $acc[ $key ] : 0 ) + (int) $value;
				}
				return $acc;
			},
			[]
		);
	}

	/**
	 * Parse CSV stats file ( compatible PHP < 5.3 ).
	 *
	 * @param int $id Post/ad ID.
	 *
	 * @return array
	 */
	private function parse_csv( $id ): array {
		$file   = get_attached_file( $id );
		$result = [
			'impressions' => [],
			'clicks'      => [],
			'ads'         => [],
			'status'      => true,
		];
		WP_Filesystem();
		global $wp_filesystem;
		$data = $wp_filesystem->get_contents( $file );
		if ( ! $data ) {
			// Ureadable file.
			return [
				'status' => false,
				'msg',
				__( 'unable to read file', 'advanced-ads-tracking' ),
			];
		}
		// Remove evntual BOM.
		$bom  = pack( 'H*', 'EFBBBF' );
		$data = preg_replace( "/^$bom/", '', $data );

		$lines = explode( "\n", $data );

		$lines = array_slice( $lines, 1 );
		foreach ( $lines as $line ) {
			if ( empty( $line ) ) {
				continue;
			}
			$cells       = [];
			$_cells      = explode( ',', $line );
			$cells_count = count( $_cells );

			if ( $cells_count > 5 ) {
				// Some extra commas are present in the ad title.
				foreach ( $_cells as $i => $value ) {
					if ( $i < 4 ) {
						$cells[] = $value;
					} else {
						$_title  = array_slice( $_cells, 4 );
						$cells[] = implode( ',', $_title );
						break;
					}
				}
			} else {
				// No extra commas.
				$cells = $_cells;
			}

			$cells = array_map( [ $this, 'trim_outer_quotes' ], $cells );
			$ts    = (int) str_replace( '-', '', $cells[0] );

			// Impressions.
			if ( ! isset( $result['impressions'][ $ts ] ) ) {
				$result['impressions'][ $ts ] = [];
			}
			$result['impressions'][ $ts ][ $cells[1] ] = (int) $cells[2];

			// Clicks.
			if ( ! isset( $result['clicks'][ $ts ] ) ) {
				$result['clicks'][ $ts ] = [];
			}
			$result['clicks'][ $ts ][ $cells[1] ] = (int) $cells[3];

			// Ad title.
			if ( ! isset( $result['ads'][ $cells[1] ] ) ) {
				$result['ads'][ $cells[1] ] = $cells[4];
			}
		}

		$firstdate = key( $result['impressions'] );
		end( $result['impressions'] );
		$lastdate = key( $result['impressions'] );
		reset( $result['impressions'] );
		$result['firstdate'] = substr( $firstdate, 0, 4 ) . '-' . substr( $firstdate, 4, 2 ) . '-' . substr( $firstdate, 6, 2 );
		$result['lastdate']  = substr( $lastdate, 0, 4 ) . '-' . substr( $lastdate, 4, 2 ) . '-' . substr( $lastdate, 6, 2 );

		return $result;
	}

	/**
	 * Remove outer quotes from CSV field.
	 *
	 * @param string $elem the current CSV field.
	 *
	 * @return string
	 */
	private function trim_outer_quotes( $elem ): string {
		if ( empty( $elem ) ) {
			return $elem;
		}

		if ( '"' === $elem[0] && '"' === $elem[ strlen( $elem ) - 1 ] ) {
			return substr( $elem, 1, - 1 );
		}

		return $elem;
	}

	/**
	 * Split date from CSV.
	 *
	 * @param string $d Date.
	 *
	 * @return string
	 */
	private function split_date( $d ): string {
		return substr( $d, 0, 4 ) . '-' . substr( $d, 4, 2 ) . '-' . substr( $d, 6, 2 );
	}

	/**
	 * Prepare data from CSV before sending it back to the browser
	 *
	 * @param array  $data    CSV data.
	 * @param string $period  Period.
	 * @param string $from    From date.
	 * @param string $to      To date.
	 * @param string $groupby Group by.
	 *
	 * @return array
	 */
	private function prepare_stats_from_file( $data, $period, $from, $to, $groupby ) {
		$result = [
			'status' => true,
			'stats'  => [],
		];
		$_from  = (int) str_replace( [ '-', '/' ], [ '', '' ], $from );
		$_to    = (int) str_replace( [ '-', '/' ], [ '', '' ], $to );

		$periodstart = '';
		$periodend   = '';

		// Define the timetsamp for the first and last record to return.
		switch ( $period ) {
			case 'firstmonth':
				$firstdate   = date_create( $data['firstdate'] );
				$_from       = (int) $firstdate->format( 'Ym01' );
				$_to         = (int) $firstdate->format( 'Ymt' );
				$periodstart = $firstdate->format( 'Y-m-01' );
				$periodend   = $firstdate->format( 'Y-m-t' );
				break;
			case 'latestmonth':
				$lastdate    = date_create( $data['lastdate'] );
				$_from       = (int) $lastdate->format( 'Ym01' );
				$_to         = (int) $lastdate->format( 'Ymd' );
				$periodstart = $lastdate->format( 'Y-m-01' );
				$periodend   = $lastdate->format( 'Y-m-t' );
				break;
			default:
				$periodstart = $from;
				$periodend   = $to;
		}

		$imprs        = [];
		$clicks       = [];
		$ad_ids       = array_keys( $data['ads'] );
		$date         = null;
		$group_clicks = [];
		$group_imprs  = [];

		end( $data['impressions'] );
		$last_ts = key( $data['impressions'] );
		reset( $data['impressions'] );

		foreach ( $data['impressions'] as $ts => $_imprs ) {
			switch ( $groupby ) {
				case 'month':
					if ( $ts >= $_from && $ts <= $_to ) {
						$_date = date_create( self::split_date( $ts ) );
						if ( null === $date ) {
							$date = $_date->format( 'Y-m' );
						}
						if ( $ts === $last_ts ) {
							foreach ( $ad_ids as $ad_id ) {
								if ( ! isset( $group_imprs[ $ad_id ] ) ) {
									$group_imprs[ $ad_id ] = 0;
								}
								if ( ! isset( $group_clicks[ $ad_id ] ) ) {
									$group_clicks[ $ad_id ] = 0;
								}
								$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
								if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
									$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
								}
							}
						}
						if ( $ts === $last_ts || $date !== $_date->format( 'Y-m' ) ) {
							$imprs[ $date ]  = $group_imprs;
							$clicks[ $date ] = $group_clicks;

							$date         = $_date->format( 'Y-m' );
							$group_clicks = [];
							$group_imprs  = [];
						}
						foreach ( $ad_ids as $ad_id ) {
							if ( ! isset( $group_imprs[ $ad_id ] ) ) {
								$group_imprs[ $ad_id ] = 0;
							}
							if ( ! isset( $group_clicks[ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] = 0;
							}
							$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
							if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
							}
						}
					} elseif ( $ts > $_to && ! empty( $group_imprs ) ) {
						foreach ( $ad_ids as $ad_id ) {
							if ( ! isset( $group_imprs[ $ad_id ] ) ) {
								$group_imprs[ $ad_id ] = 0;
							}
							if ( ! isset( $group_clicks[ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] = 0;
							}
							$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
							if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
							}
						}
						$imprs[ $date ]  = $group_imprs;
						$clicks[ $date ] = $group_clicks;
						$group_clicks    = [];
						$group_imprs     = [];
					}
					break;
				case 'week':
					if ( $ts >= $_from && $ts <= $_to ) {
						$_date = date_create( self::split_date( $ts ) );
						if ( null === $date ) {
							$date = $_date->format( 'o-\WW' );
						}
						if ( $ts === $last_ts ) {
							foreach ( $ad_ids as $ad_id ) {
								if ( ! isset( $group_imprs[ $ad_id ] ) ) {
									$group_imprs[ $ad_id ] = 0;
								}
								if ( ! isset( $group_clicks[ $ad_id ] ) ) {
									$group_clicks[ $ad_id ] = 0;
								}
								$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
								if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
									$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
								}
							}
						}
						if ( $ts === $last_ts || $date !== $_date->format( 'o-\WW' ) || $ts > $_to ) {
							$imprs[ $date ]  = $group_imprs;
							$clicks[ $date ] = $group_clicks;

							$date         = $_date->format( 'o-\WW' );
							$group_clicks = [];
							$group_imprs  = [];
						}
						foreach ( $ad_ids as $ad_id ) {
							if ( ! isset( $group_imprs[ $ad_id ] ) ) {
								$group_imprs[ $ad_id ] = 0;
							}
							if ( ! isset( $group_clicks[ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] = 0;
							}
							$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
							if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
							}
						}
					} elseif ( $ts > $_to && ! empty( $group_imprs ) ) {
						foreach ( $ad_ids as $ad_id ) {
							if ( ! isset( $group_imprs[ $ad_id ] ) ) {
								$group_imprs[ $ad_id ] = 0;
							}
							if ( ! isset( $group_clicks[ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] = 0;
							}
							$group_imprs[ $ad_id ] += (int) $_imprs[ $ad_id ];
							if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
								$group_clicks[ $ad_id ] += (int) $data['clicks'][ $ts ][ $ad_id ];
							}
						}
						$imprs[ $date ]  = $group_imprs;
						$clicks[ $date ] = $group_clicks;
						$group_clicks    = [];
						$group_imprs     = [];
					}
					break;
				default:
					$date = self::split_date( $ts );
					if ( $ts >= $_from && $ts <= $_to ) {
						if ( ! isset( $imprs[ $date ] ) ) {
							$imprs[ $date ] = [];
						}
						if ( ! isset( $clicks[ $date ] ) ) {
							$clicks[ $date ] = [];
						}
						foreach ( $ad_ids as $ad_id ) {
							if ( isset( $_imprs[ $ad_id ] ) ) {
								$imprs[ $date ][ $ad_id ] = $_imprs[ $ad_id ];
							}
							if ( isset( $data['clicks'][ $ts ][ $ad_id ] ) ) {
								$clicks[ $date ][ $ad_id ] = $data['clicks'][ $ts ][ $ad_id ];
							}
						}
					}
			}
		}

		if ( $imprs ) {
			// Prepare jqplot and datatable variables that depend on date of first record ( if any record is found ).
			$formatstring = '%b&nbsp;%#d';
			$firstdate    = key( $imprs );

			switch ( $groupby ) {
				case 'month':
					$formatstring = '%B';
					$firstdate    = '';
					break;
				case 'week':
					/* translators: %b: month, %#d: day */
					$formatstring = _x( 'from %b&nbsp;%#d', 'format for week group in stats table', 'advanced-ads-tracking' );
					$firstdate    = date( 'Y-m-d', strtotime( $firstdate . ' -1 week' ) ); // phpcs:ignore
					break;
				default:
					$firstdate = date( 'Y-m-d', strtotime( $firstdate . ' -1 day' ) ); // phpcs:ignore
			}

			$result['stats']['xAxisThickformat'] = $formatstring;
			$result['stats']['firstDate']        = $firstdate;
			$result['stats']['impr']             = $imprs;
			$result['stats']['click']            = $clicks;
			$result['stats']['periodEnd']        = $periodend;
			$result['stats']['periodStart']      = $periodstart;
			$result['stats']['ads']              = $data['ads'];
		}

		return $result;
	}
}
