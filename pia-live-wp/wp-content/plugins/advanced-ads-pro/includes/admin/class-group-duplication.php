<?php
/**
 * Admin Groups Listing Page.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Admin;

use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Duplicate Group.
 */
class Group_Duplication implements Integration_Interface {

	private const DUPLICATE_ACTION_NAME = 'advanced_ads_duplicate_group';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( Constants::TAXONOMY_GROUP . '_row_actions', [ $this, 'render_row_actions' ], 10, 2 );
		add_action( 'admin_action_' . self::DUPLICATE_ACTION_NAME, [ $this, 'duplicate_group' ] );
	}

	/**
	 * Renders the row actions for a group.
	 *
	 * @param array $actions An array of row actions.
	 * @param Group $group   The group object.
	 *
	 * @return array The modified array of row actions.
	 */
	public function render_row_actions( $actions, $group ): array {
		$actions = $this->render_duplicate_link( $actions, $group );
		return $actions;
	}

	/**
	 * Renders the duplicate link for a group.
	 *
	 * This function checks if the current user has the permission to edit the group.
	 * If the user has the permission, it generates a duplicate link for the group.
	 * The link allows the user to create a copy of the group.
	 *
	 * @param  array $actions An array of row actions for the group.
	 * @param  Group $group   The group object.
	 *
	 * @return array The updated array of actions with the duplicate link.
	 */
	public function render_duplicate_link( $actions, $group ): array {
		if ( ! Group::can_current_user_edit_group() ) {
			return $actions;
		}

		$action = add_query_arg(
			[
				'action'   => self::DUPLICATE_ACTION_NAME,
				'group_id' => $group->get_id(),
			],
			admin_url( 'admin.php' )
		);

		$actions['duplicate-group'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			wp_nonce_url( $action, 'duplicate-group-' . $group->get_id() ),
			esc_attr__( 'Create a copy of this group', 'advanced-ads-pro' ),
			esc_html__( 'Duplicate', 'advanced-ads-pro' )
		);

		return $actions;
	}

	/**
	 * Duplicate a group.
	 *
	 * This method duplicates a group by creating a copy of it with a "(copy)" suffix in the title.
	 * If the duplication is successful, the user is redirected to the edit page of the new group.
	 * If the duplication fails, the user is redirected to the groups listing page.
	 *
	 * @since 2.26.0
	 */
	public function duplicate_group(): void {
		// Early bail!!
		$group_id = Params::get( 'group_id', 0, FILTER_VALIDATE_INT );
		if ( ! $group_id ) {
			return;
		}

		check_admin_referer( 'duplicate-group-' . $group_id );

		if (
			self::DUPLICATE_ACTION_NAME !== Params::get( 'action' ) ||
			! Group::can_current_user_edit_group()
		) {
			return;
		}

		$group = wp_advads_get_group( $group_id );

		if ( ! $group && wp_safe_redirect( admin_url( 'admin.php?page=advanced-ads-groups' ) ) ) {
			exit;
		}

		$copy_suffix = ' (' . _x( 'copy', 'noun', 'advanced-ads-pro' ) . ' at ' . current_time( 'Y-m-d H:i:s' ) . ')';
		$new_group   = clone $group;
		$new_group->set_id( null ); // reset the ID to save as a new group.
		$new_group->set_name( $new_group->get_name() . $copy_suffix );
		$new_group_id = $new_group->save();

		// Set the taxonomy relationships on the new object.
		$ad_ids = get_objects_in_term( $group_id, Constants::TAXONOMY_GROUP );
		foreach ( $ad_ids as $ad_id ) {
			wp_set_object_terms( $ad_id, $new_group_id, Constants::TAXONOMY_GROUP, true );
		}

		if ( wp_safe_redirect( $new_group->get_edit_link() ) ) {
			exit;
		}
	}
}
