<?php
/**
 * Admin Ad List Table.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Admin;

use AdvancedAds\Abstracts\Ad;
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
		add_filter( 'advanced-ads-ad-columns', [ $this, 'add_columns' ] );
		add_action( 'advanced-ads-ad-render-columns', [ $this, 'render_columns' ], 10, 2 );
		add_filter( 'default_hidden_columns', [ $this, 'set_hidden_columns' ], 10, 2 );
		add_filter( 'advanced_ads_optional_filters', [ $this, 'add_optional_filters' ] );
	}

	/**
	 * Add columns to view.
	 *
	 * @param array $columns columns.
	 *
	 * @return array
	 */
	public function add_columns( array $columns ): array {
		$columns['ad_displayonce'] = __( 'Display Once', 'advanced-ads-pro' );

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
		if ( 'ad_displayonce' === $column ) {
			$display_once = $ad->get_prop( 'once_per_page' ) ?? false;

			include AA_PRO_ABSPATH . 'views/admin/tables/ads/column-displayonce.php';
		}
	}

	/**
	 * Hidden columns.
	 *
	 * @param array     $hidden default hidden columns.
	 * @param WP_Screen $screen current screen.
	 *
	 * @return array|mixed
	 */
	public function set_hidden_columns( $hidden, $screen ) {
		if ( isset( $screen->id ) && 'edit-advanced_ads' === $screen->id ) {
			$hidden = array_merge( $hidden, [ 'ad_displayonce' ] );
		}
		return $hidden;
	}

	/**
	 * Adds an optional filter to the list of filters.
	 *
	 * @param array $optional_filters The array of optional filters.
	 *
	 * @return array
	 */
	public function add_optional_filters( $optional_filters ): array {
		$optional_filters['all_displayonce'] = __( 'Display Once', 'advanced-ads-pro' );

		return $optional_filters;
	}
}
