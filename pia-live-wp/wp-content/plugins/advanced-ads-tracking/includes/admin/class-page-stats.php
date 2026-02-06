<?php
/**
 * Admin Page Stats.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Abstracts\Screen;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Tracking\Utilities\Data;
use AdvancedAds\Framework\Utilities\Params;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Page Stats.
 */
class Page_Stats extends Screen {

	/**
	 * Screen unique id.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'stats';
	}

	/**
	 * Get the order number of the screen.
	 *
	 * @return int
	 */
	public function get_order(): int {
		return 10;
	}

	/**
	 * Register screen into WordPress admin area.
	 *
	 * @return void
	 */
	public function register_screen(): void {
		$hook = add_submenu_page(
			ADVADS_SLUG,
			__( 'Statistics', 'advanced-ads-tracking' ),
			__( 'Statistics', 'advanced-ads-tracking' ),
			Conditional::user_cap( 'advanced_ads_edit_ads' ),
			ADVADS_SLUG . '-stats',
			[ $this, 'display' ]
		);

		$this->set_hook( $hook );
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_advads_tracking()->registry->enqueue_style( 'admin-styles' );
		wp_advads_tracking()->registry->enqueue_style( 'jqplot' );
		wp_advads_tracking()->registry->enqueue_style( 'datatables' );
		wp_advads_tracking()->registry->enqueue_style( 'jquery-ui' );

		wp_advads_tracking()->registry->enqueue_script( 'screen-stats-file' );
	}

	/**
	 * Display screen content.
	 *
	 * @return void
	 */
	public function display(): void {
		$advads_stats   = Params::get( 'advads-stats', [], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$from           = $advads_stats['from'] ?? null;
		$to             = $advads_stats['to'] ?? null;
		$groupby        = $advads_stats['groupby'] ?? null;
		$display_filter = wp_unslash( Params::request( 'advads-stats-filter', 'all-ads' ) );
		$group_format   = 'Y-m-d';
		$period         = $advads_stats['period'] ?? null;
		$periods        = Data::get_periods();

		// Load groupby options.
		// Group format, axis label, value conversion for graph.
		$groupbys = [
			'day'   => [ 'Y-m-d', __( 'day', 'advanced-ads-tracking' ), _x( 'Y-m-d', 'date format on stats page', 'advanced-ads-tracking' ) ],
			'week'  => [ 'o-\WW', __( 'week', 'advanced-ads-tracking' ), _x( 'Y-m-d', 'date format on stats page', 'advanced-ads-tracking' ) ],
			'month' => [ 'Y-m', __( 'month', 'advanced-ads-tracking' ), _x( 'Y-m', 'date format on stats page', 'advanced-ads-tracking' ) ],
		];

		// TODO: handle undefined options (should not occur).
		if ( ! isset( $periods[ $period ] ) ) {
			$period = null;
		}

		if ( ! isset( $groupbys[ $groupby ] ) ) {
			$groupby = null;
		} else {
			$group_format = $groupbys[ $groupby ][0];
		}

		$impression_stats = null;
		$click_stats      = null;

		if ( isset( $advads_stats['ads'] ) ) {
			$stat_args        = [
				'ad_id'       => 'all',
				'period'      => $period,
				'groupby'     => $groupby,
				'groupFormat' => $group_format,
				'from'        => $from,
				'to'          => $to,
			];
			$impression_stats = Database::load_stats( $stat_args, Database::get_impression_table() );
			$click_stats      = Database::load_stats( $stat_args, Database::get_click_table() );
		}

		include_once AA_TRACKING_ABSPATH . 'views/admin/page-stats.php';
	}
}
