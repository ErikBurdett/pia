<?php
/**
 * Admin Duplicate Placement.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Utilities\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Duplicate Placement.
 */
class Duplicate_Placement implements Integration_Interface {
	/**
	 * Admin action
	 *
	 * @var string
	 */
	private const ACTION = 'advanced_ads_duplicate_placement';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'post_row_actions', [ $this, 'action_link' ], 10, 2 );
		add_action( 'admin_action_' . self::ACTION, [ $this, 'duplicate' ] );
	}

	/**
	 * Duplicate a placement
	 *
	 * @return void
	 */
	public function duplicate() {
		// Early bail!!
		$placement_id = Params::get( 'id', 0, FILTER_VALIDATE_INT );
		if ( ! $placement_id ) {
			return;
		}

		check_admin_referer( 'duplicate-placement-' . $placement_id );

		if ( self::ACTION !== Params::get( 'action' ) || ! Conditional::user_cap( 'advanced_ads_manage_placements' ) ) {
			return;
		}

		$placement = wp_advads_get_placement( $placement_id );

		if ( ! $placement && wp_safe_redirect( admin_url( 'edit.php?post_type=advanced_ads_plcmnt' ) ) ) {
			exit;
		}

		$copy_suffix   = ' (' . _x( 'copy', 'noun', 'advanced-ads-pro' ) . ' at ' . current_time( 'Y-m-d H:i:s' ) . ')';
		$new_placement = clone $placement;
		$new_placement->set_id( null ); // reset the ID to save as a new placement.
		$new_placement->set_title( $new_placement->get_title() . $copy_suffix );
		$new_placement->set_item( '' );
		$new_placement->set_prop( 'test_id', null );
		$new_id = $new_placement->save();

		if ( wp_safe_redirect( admin_url( 'edit.php?post_type=advanced_ads_plcmnt#modal-placement-edit-' . $new_id ) ) ) {
			exit;
		}
	}

	/**
	 * Add duplicate links on placement table
	 *
	 * @param array    $actions existing actions.
	 * @param \WP_Post $post the post.
	 *
	 * @return array the modified actions list.
	 */
	public function action_link( $actions, $post ) {
		if ( Constants::POST_TYPE_PLACEMENT !== $post->post_type || ! Conditional::user_cap( 'advanced_ads_manage_placements' ) ) {
			return $actions;
		}

		$action = add_query_arg(
			[
				'action' => self::ACTION,
				'id'     => $post->ID,
			],
			admin_url( 'admin.php' )
		);

		$actions['duplicate-placement'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			wp_nonce_url( $action, 'duplicate-placement-' . $post->ID ),
			esc_attr__( 'Create a copy of this placement', 'advanced-ads-pro' ),
			esc_html__( 'Duplicate', 'advanced-ads-pro' )
		);

		return $actions;
	}
}
