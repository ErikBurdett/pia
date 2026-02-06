<?php
/**
 * Main module class
 *
 * @package AdvancedAds\Pro\Modules\bbPress
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Modules\bbPress;

use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstrap the bbPress module
 */
class BBPress implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'init' ], 31 );
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		$placements = wp_advads_get_placements();

		foreach ( $placements as $placement ) {
			$this->register_hook( $placement, 'bbPress comment', 'bbPress_comment_hook' );
			$this->register_hook( $placement, 'bbPress static', 'bbPress_static_hook' );
		}
	}

	/**
	 * Inject during hooks found in the placement options
	 *
	 * @return void
	 */
	public function execute_hook(): void {
		$placements = wp_advads_get_placements();

		foreach ( $placements as $id => $placement ) {
			if (
				( $placement->is_type( 'bbPress comment' ) && $this->is_comment_hook( $placement ) )
				|| ( $placement->is_type( 'bbPress static' ) && $this->is_static_content_hook( $placement ) )
			) {
				the_ad_placement( $id );
			}
		}
	}

	/**
	 * Check if can render static content placement
	 *
	 * @param Placement $placement the placement.
	 *
	 * @return bool
	 */
	private function is_static_content_hook( $placement ) {
		$hook  = current_filter();
		$check = str_replace( ' ', '_', 'bbp_' . $placement->get_prop( 'bbPress_static_hook' ) );
		return $placement->get_prop( 'bbPress_static_hook' ) && $hook === $check;
	}

	/**
	 * Check if can render reply placement
	 *
	 * @param Placement $placement the placement.
	 *
	 * @return bool
	 */
	private function is_comment_hook( $placement ) {
		$hook  = current_filter();
		$check = str_replace( ' ', '_', 'bbp_' . $placement->get_prop( 'bbPress_comment_hook' ) );

		return $placement->get_prop( 'pro_bbPress_comment_pages_index' )
			&& $placement->get_prop( 'bbPress_comment_hook' )
			&& $hook === $check
			&& (int) $placement->get_prop( 'pro_bbPress_comment_pages_index' ) === did_action( $hook );
	}

	/**
	 * Register the bbPress hook.
	 *
	 * @param Placement $placement Placement instance.
	 * @param string    $type      Type of placement.
	 * @param string    $prop      Property name.
	 *
	 * @return void
	 */
	private function register_hook( $placement, $type, $prop ): void {
		if ( $placement->is_type( $type ) && $placement->get_prop( $prop ) ) {
			$hook = str_replace( ' ', '_', 'bbp_' . $placement->get_prop( $prop ) );
			add_action( $hook, [ $this, 'execute_hook' ] );
		}
	}
}
