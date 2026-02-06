<?php
/**
 * Admin Page Database.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Screen;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Tracking\Debugger;
use AdvancedAds\Tracking\Db_Operations;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Page Database.
 */
class Page_Database extends Screen {
	/**
	 * Screen unique id.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'stats-database';
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
			Constants::HIDDEN_PAGE_SLUG,
			__( 'Tracking database', 'advanced-ads-tracking' ),
			null,
			'manage_options',
			'advads-tracking-db-page',
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
		wp_advads_tracking()->registry->enqueue_style( 'jquery-ui' );
		wp_advads_tracking()->registry->enqueue_script( 'screen-database' );
	}

	/**
	 * Display screen content.
	 *
	 * @return void
	 */
	public function display(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$_request = wp_unslash( $_REQUEST );
		if (
			isset( $_request['delete-debug-nonce'] ) &&
			false !== wp_verify_nonce( $_request['delete-debug-nonce'], 'delete-debug-log' ) &&
			file_exists( Debugger::get_debug_file_path() )
		) {
			require_once AA_TRACKING_ABSPATH . 'views/admin/page-database/deleted-ads-form.php';
			return;
		}

		$nonce             = wp_create_nonce( 'advads_tracking_dbop' );
		$impressions_table = Database::get_impression_table();
		$clicks_table      = Database::get_click_table();
		$db_size           = Db_Operations::get_instance()->get_db_size();
		$date_format       = get_option( 'date_format' );
		$deleted_ads       = Db_Operations::get_instance()->get_deleted_ads();
		$debug_option      = get_option( Debugger::DEBUG_OPT, false );
		$debug_ad          = false;
		$debug_time        = [
			'hours' => 0,
			'mins'  => 0,
		];

		if ( $debug_option ) {
			$rem_time            = $debug_option['time'] + ( Debugger::DEBUG_HOURS * 3600 ) - time();
			$debug_time['hours'] = floor( $rem_time / 3600 );
			$debug_time['mins']  = floor( ( $rem_time - ( 3600 * $debug_time['hours'] ) ) / 60 );
		}

		if ( $debug_option && is_numeric( $debug_option['id'] ) ) {
			$debug_ad = get_post( $debug_option['id'] );
		}

		$export_periods_args = [
			'period-options' => Db_Operations::get_instance()->get_export_periods(),
		];

		$remove_periods_args = [
			'custom'         => false,
			'period-options' => Db_Operations::get_instance()->get_remove_periods(),
		];

		$ads_with_any_status = Database::get_all_ads();
		$ads_published       = wp_advads_ad_query(
			[
				'post_status' => [ 'publish' ],
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		)->posts;

		$delete_debug_link = wp_nonce_url( Helpers::get_database_tool_link(), 'delete-debug-log', 'delete-debug-nonce' );

		include_once AA_TRACKING_ABSPATH . 'views/admin/page-database/db-operations.php';
		$this->display_time();
	}

	/**
	 * Display the time settings
	 *
	 * @return void
	 */
	private function display_time(): void {
		$time_format = _x( 'Y-m-d H:i:s', 'current time format on stats page', 'advanced-ads-tracking' );
		$time_wp     = get_date_from_gmt( gmdate( 'Y-m-d H:i:s' ), $time_format );
		$time_db     = Helpers::get_date_from_db( Helpers::get_timestamp(), $time_format );
		$time_utc    = gmdate( $time_format );

		include_once AA_TRACKING_ABSPATH . 'views/admin/page-database/db-operations-time.php';
	}
}
