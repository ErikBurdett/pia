<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Abstracts\Types;
use AdvancedAds\Pro\Modules\Grids\Type_Grid;

/**
 * Class Grids
 */
class Advanced_Ads_Pro_Module_Grids {

	/**
	 * The constructor.
	 */
	public function __construct() {
		add_filter( 'advanced-ads-group-types-manager', [ $this, 'add_group_type' ] );
		add_filter( 'advanced-ads-group-output-ad-ids', [ $this, 'output_ad_ids' ], 10, 5 );
		add_filter( 'advanced-ads-group-grid-displayed-ad-count', [ $this, 'adjust_ad_group_displayed_ad_count' ], 10, 2 );
		add_filter( 'advanced-ads-group-ad-count', [ $this, 'adjust_ad_group_number' ], 10, 2 );
		add_filter( 'advanced-ads-group-output-array', [ $this, 'output_markup' ], 10, 2 );
		add_filter( 'advanced-ads-pro-passive-cb-group-data', [ $this, 'add_grid_markup_passive' ], 10, 3 );
	}

	/**
	 * Add grid group type
	 *
	 * @param Types $manager Group types manager.
	 *
	 * @return void
	 */
	public function add_group_type( $manager ): void {
		$manager->register_type( Type_Grid::class );
	}

	/**
	 * Get ids from ads in the order they should be displayed
	 *
	 * @param array  $ordered_ad_ids Ad ids in the order from the main plugin.
	 * @param string $type           Group type.
	 * @param array  $ads            Array with ad objects.
	 * @param array  $weights        Array with ad weights.
	 * @param Group  $group          Group instance.
	 *
	 * @return array $ad_ids
	 */
	public function output_ad_ids( $ordered_ad_ids, $type, $ads, $weights, $group ) {
		// For some reason a client had an issue with $group not being the correct class.
		if ( ! is_a_group( $group ) ) {
			return $ordered_ad_ids;
		}

		if ( 'grid' === $type ) {
			if ( $group->get_prop( 'random' ) ) {
				return $group->shuffle_ads();
			} else {
				return array_keys( $weights );
			}
		}

		return $ordered_ad_ids;
	}

	/**
	 * Adjust the ad group number if the ad type is a grid.
	 *
	 * @param int|string $ad_count The number of ads, is an integer or string 'all'.
	 * @param Group      $group    Group instance.
	 *
	 * @return int|string The number of ads, either an integer or string 'all'.
	 */
	public function adjust_ad_group_number( $ad_count, $group ) {
		if ( $group->is_type( 'grid' ) ) {
			return $group->get_columns() * $group->get_rows();
		}

		return $ad_count;
	}

	/**
	 * Adjust ad count in group page for grid groups
	 *
	 * @param int   $displayed_ad_count Displayed ad count from the base plugin.
	 * @param Group $group              Group instance.
	 *
	 * @return int
	 */
	public function adjust_ad_group_displayed_ad_count( $displayed_ad_count, $group ) {
		return $group->get_columns() * $group->get_rows();
	}

	/**
	 * Add extra output markup for grid
	 *
	 * @param array $ad_content Array with ad contents.
	 * @param Group $group      Group instance.
	 *
	 * @return array $ad_content with extra markup
	 */
	public function output_markup( array $ad_content, Group $group ) {
		if ( count( $ad_content ) <= 1 || ! $group->is_type( 'grid' ) ) {
			return $ad_content;
		}

		$i      = 1;
		$markup = $this->get_grid_markup( $group );
		foreach ( $ad_content as $_key => $_content ) {

			foreach ( $markup['each'] as $_column_index => $_format ) {
				if ( 'all' === $_column_index || 0 === $i % $_column_index ) {
					$ad_content[ $_key ] = sprintf( $_format, $_content );
					break;
				}
			}
			++$i;
		}

		array_unshift( $ad_content, $markup['before'] );
		array_push( $ad_content, $markup['after'] );

		return $ad_content;
	}

	/**
	 * Add grid markup to passive cache-busting.
	 *
	 * @param array  $group_data Group data.
	 * @param Group  $group      Group instance.
	 * @param string $element_id Element ID.
	 */
	public function add_grid_markup_passive( $group_data, Group $group, $element_id ) {
		if ( $element_id && $group->is_type( 'grid' ) ) {
			$group_data['random']       = $group->get_prop( 'grid.random' );
			$group_data['group_wrap'][] = $this->get_grid_markup( $group );
		}

		return $group_data;
	}


	/**
	 * Get markup to inject around each ad and around entire set of ads.
	 *
	 * @param Group $group Group instance.
	 *
	 * @return array
	 */
	public function get_grid_markup( Group $group ) {
		$columns               = $group->get_columns();
		$prefix                = wp_advads()->get_frontend_prefix();
		$grid_id               = $prefix . 'grid-' . $group->get_id();
		$min_width             = $group->get_prop( 'min_width' );
		$full_width_breakpoint = absint( $group->get_prop( 'full_width_breakpoint' ) ) ?? false;
		$inner_margin          = absint( $group->get_prop( 'inner_margin' ) );
		$width                 = absint( ( 100 - ( $columns - 1 ) * $inner_margin ) / $columns );

		// Generate styles.
		$css = "<style>#$grid_id{list-style:none;margin:0;padding:0;overflow:hidden;}"
			. "#$grid_id>li{float:left;width:$width%;min-width:{$min_width}px;list-style:none;margin:0 $inner_margin% $inner_margin% 0;;padding:0;overflow:hidden;}"
			. "#$grid_id>li.last{margin-right:0;}"
			. "#$grid_id>li.last+li{clear:both;}";

		// Add media query if there was a full width breakpoint set.
		if ( ! empty( $full_width_breakpoint ) ) {
			$css .= "@media only screen and (max-width:{$full_width_breakpoint}px) {#$grid_id>li{width:100%;}}";
		}

		$css .= '</style>';

		return [
			'before'  => '<ul id="' . $grid_id . '">',
			'after'   => '</ul>' . $css,
			'each'    => [
				$columns => '<li class="last">%s</li>',
				'all'    => '<li>%s</li>',
			],
			'min_ads' => 2,
		];
	}
}
