<?php
/**
 * BuddyPress module admin
 *
 * @package AdvancedAds\Pro\Modules\BuddyPress
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Modules\BuddyPress;

use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-placement-options-after', [ $this, 'placement_options' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'wp_ajax_advads-pro-buddyboss-render-xprofile-field', [ $this, 'render_xprofile_field_ajax' ] );
	}

	/**
	 * Register options for the BuddyPress placement
	 *
	 * @param string    $slug      placement slug.
	 * @param Placement $placement the placement.
	 *
	 * @return void
	 */
	public function placement_options( $slug, $placement ) {
		if ( $placement->is_type( 'buddypress' ) ) {
			$placement_options    = $placement->get_data();
			$buddypress_positions = $this->get_buddypress_hooks();
			$current              = BuddyPress::get_hook_from_placement_options( $placement );
			$activity_type        = $placement_options['activity_type'] ?? 'any';
			$hook_repeat          = ! empty( $placement_options['hook_repeat'] );
			$index                = ( isset( $placement_options['pro_buddypress_pages_index'] ) ) ? \Advanced_Ads_Pro_Utils::absint( $placement_options['pro_buddypress_pages_index'], 1 ) : 1;

			require AA_PRO_ABSPATH . 'modules/buddypress/views/position-option.php';
		}
	}

	/**
	 * Load the hooks relevant for BuddyPress/BuddyBoss
	 *
	 * @return array list of hooks for BuddyPress depending on the BP theme
	 */
	public function get_buddypress_hooks() {
		if ( ! BuddyPress::is_legacy_theme() ) {
			return [
				__( 'Activity Entry', 'advanced-ads-pro' ) => [
					'bp_after_activity_entry' => 'after activity entry',
				],
			];
		}

		// Return legacy hooks.
		return [
			__( 'Activity Entry', 'advanced-ads-pro' ) => [
				'bp_before_activity_entry'          => 'before activity entry',
				'bp_activity_entry_content'         => 'activity entry content',
				'bp_after_activity_entry'           => 'after activity entry',
				'bp_before_activity_entry_comments' => 'before activity entry comments',
				'bp_activity_entry_comments'        => 'activity entry comments',
				'bp_after_activity_entry_comments'  => 'after activity entry comments',
			],
			__( 'Group List', 'advanced-ads-pro' )     => [
				'bp_directory_groups_item' => 'directory groups item',
			],
			__( 'Member List', 'advanced-ads-pro' )    => [
				'bp_directory_members_item' => 'directory members item',
			],
		];
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_admin_scripts() {
		if ( ! Conditional::is_screen_advanced_ads() ) {
			return;
		}

		wp_enqueue_script( 'advanced-ads-pro/buddyboss-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', [ 'jquery' ], AAP_VERSION, true );
	}

	/**
	 * Renders a html field corresponding to the currently selected field type.
	 */
	public function render_xprofile_field_ajax() {
		check_ajax_referer( 'advanced-ads-admin-ajax-nonce', 'nonce' );

		$field_name = Params::post( 'field_name' );
		$field_type = Params::post( 'field_type' );

		if (
			! Conditional::user_can( 'advanced_ads_edit_ads' )
			|| ! $field_name || ! $field_type
		) {
			die;
		}

		self::render_xprofile_field(
			preg_replace( '/[^a-z0-9\[\]]/', '', $field_name ),
			preg_replace( '/[^a-z0-9]/', '', $field_type ),
			''
		);
		die;
	}

	/**
	 * Renders a html field corresponding to the currently selected field type.
	 *
	 * @param string $name       Field name.
	 * @param string $field_type type Field type.
	 * @param string $value      Field value.
	 */
	public static function render_xprofile_field( $name, $field_type, $value = '' ) {
		if ( function_exists( 'bp_get_active_member_types' ) && BuddyPress::FIELD_MEMBERTYPES === $field_type ) {
			$bp_active_member_types = \bp_get_active_member_types();
			if ( ! empty( $bp_active_member_types ) ) {
				printf(
					'<select name="%s[value]" class="advanced-ads-buddyboss-xprofile-dynamic-field">',
					esc_attr( $name )
				);
				foreach ( $bp_active_member_types as $bp_active_member_type ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $bp_active_member_type ),
						selected( $bp_active_member_type, (int) $value, false ),
						esc_attr( get_post_meta( $bp_active_member_type, '_bp_member_type_label_singular_name', true ) )
					);
				}
				echo '</select>';
			}
		} else {
			printf(
				'<input type="text" name="%s[value]" value="%s" class="advanced-ads-buddyboss-xprofile-dynamic-field">',
				esc_attr( $name ),
				esc_attr( $value )
			);
		}
	}
}
