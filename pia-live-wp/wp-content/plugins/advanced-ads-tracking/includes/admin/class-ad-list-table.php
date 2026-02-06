<?php
/**
 * Admin Ad List Table.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Database;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Ad List Table.
 */
class Ad_List_Table implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-screen-ads', [ $this, 'add_style' ] );
		add_filter( 'advanced-ads-ad-columns', [ $this, 'add_columns' ] );
		add_action( 'advanced-ads-ad-render-columns', [ $this, 'render_columns' ], 10, 2 );
		add_filter( 'default_hidden_columns', [ $this, 'set_hidden_columns' ], 10, 2 );
		add_filter( 'manage_edit-advanced_ads_sortable_columns', [ $this, 'set_sortable_columns' ] );
		add_filter( 'posts_clauses', [ $this, 'request_clauses' ], 10, 2 );
	}

	/**
	 * Add style to the ad list table.
	 *
	 * @return void
	 */
	public function add_style(): void {
		ob_start();
		?>
		.target-link-div {
			display: inline;
		}

		.target-link-div .target-link-text {
			display: none;
			position: absolute;
			background-color: #fff;
			border: 1px solid #d6d6d6;
			padding: 0.5em;
			max-width: 14%;
		}

		.target-link-div:hover .target-link-text {
			display: block;
		}
		<?php
		wp_advads()->registry->inline_style( 'screen-ads-listing', ob_get_clean() );
		wp_advads()->registry->inline_style( 'admin', '.wp-list-table th.sorted:last-of-type a,.wp-list-table th.sortable:last-of-type a {display: inline-block}' );
	}

	/**
	 * Add columns to the ad list table.
	 *
	 * @param array $columns columns.
	 *
	 * @return array
	 */
	public function add_columns( array $columns ): array {
		if ( ! Helpers::is_tracking_method( 'ga' ) ) {
			$columns['ad_stats']  = esc_attr__( 'Statistics', 'advanced-ads-tracking' );
			$columns['ad_imprs']  = esc_attr__( 'Impressions', 'advanced-ads-tracking' );
			$columns['ad_clicks'] = esc_attr__( 'Clicks', 'advanced-ads-tracking' );
			$columns['ad_ctrs']   = esc_attr__( 'CTR', 'advanced-ads-tracking' );
		}
		return $columns;
	}

	/**
	 * Render columns.
	 *
	 * @param string $column column name.
	 * @param Ad     $ad Ad instance.
	 *
	 * @return void
	 */
	public function render_columns( string $column, $ad ): void {
		switch ( $column ) {
			case 'ad_stats':
				include AA_TRACKING_ABSPATH . 'views/admin/ads/ad-list-stats-column.php';
				break;
			case 'ad_imprs':
				echo esc_html( $ad->get_impressions() );
				break;
			case 'ad_clicks':
				echo esc_html( $ad->get_clicks() );
				break;
			case 'ad_ctrs':
				echo esc_html( $ad->get_ctr() . '%' );
				break;
			default:
		}
	}

	/**
	 * Make impressions, clicks and ctr columns sortable
	 *
	 * @param array $columns sortable columns.
	 *
	 * @return array
	 */
	public function set_sortable_columns( $columns ): array {
		$columns['ad_imprs']  = [ 'impressions', __( 'Impressions', 'advanced-ads-tracking' ) ];
		$columns['ad_clicks'] = [ 'clicks', __( 'Clicks', 'advanced-ads-tracking' ) ];
		$columns['ad_ctrs']   = [ 'ctr', __( 'CTR', 'advanced-ads-tracking' ) ];

		return $columns;
	}

	/**
	 * Make impressions, clicks and ctr columns hidden by default
	 *
	 * @param array     $hidden default hidden columns.
	 * @param WP_Screen $screen current screen.
	 *
	 * @return array|mixed
	 */
	public function set_hidden_columns( $hidden, $screen ) {
		if ( isset( $screen->id ) && 'edit-advanced_ads' === $screen->id ) {
			$hidden = array_merge( $hidden, [ 'ad_imprs', 'ad_clicks', 'ad_ctrs' ] );
		}
		return $hidden;
	}

	/**
	 * Add statistics data to the query on ad overview page
	 *
	 * @param array    $clauses Clauses in current query.
	 * @param WP_Query $query   Current query object.
	 *
	 * @return mixed
	 */
	public function request_clauses( $clauses, $query ) {
		global $wpdb;

		if ( ! function_exists( 'get_current_screen' ) ) {
			return $clauses;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-advanced_ads' !== $screen->id || ! $query->is_main_query() ) {
			return $clauses;
		}

		$clicks_table      = Database::get_click_table();
		$impressions_table = Database::get_impression_table();

		$clauses['fields']  .= ', imp.count as impressions, cl.count as clicks, cl.count / imp.count as ctr';
		$clauses['join']    .= " LEFT JOIN (SELECT ad_id, SUM(count) as count from {$impressions_table} GROUP BY {$impressions_table}.ad_id) as imp ON {$wpdb->posts}.ID = imp.ad_id";
		$clauses['join']    .= " LEFT JOIN (SELECT ad_id, SUM(count) as count from {$clicks_table} GROUP BY {$clicks_table}.ad_id) as cl ON imp.ad_id = cl.ad_id";
		$clauses['groupby'] .= empty( trim( $clauses['groupby'] ) ) ? '' : ',';
		$clauses['groupby'] .= " {$wpdb->posts}.ID";

		$order = [
			'title'       => "{$wpdb->posts}.post_title",
			'impressions' => 'impressions',
			'clicks'      => 'clicks',
			'ctr'         => 'ctr',
		];

		if ( $query->query_vars['orderby'] && in_array( $query->query_vars['orderby'], $order, true ) ) {
			$clauses['orderby'] = "{$order[$query->query_vars['orderby']]} {$query->query_vars['order']}";
		}

		return $clauses;
	}
}
