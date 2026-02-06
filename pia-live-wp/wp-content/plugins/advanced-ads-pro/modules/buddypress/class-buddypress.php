<?php
/**
 * BuddyPress module
 *
 * @package AdvancedAds\Pro\Modules\BuddyPress
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Modules\BuddyPress;

use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class BuddyPress
 */
class BuddyPress implements Integration_Interface {

	/**
	 * Member Types xprofile field type.
	 */
	const FIELD_MEMBERTYPES = 'membertypes';

	/**
	 * Textbox xprofile field type.
	 */
	const FIELD_TEXTBOX = 'textbox';

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', [ $this, 'init' ], 31 );
		add_filter( 'advanced-ads-visitor-conditions', [ $this, 'visitor_conditions' ] );
		add_filter( 'advanced-ads-display-conditions', [ $this, 'display_conditions' ] );
	}

	/**
	 * Get all placements
	 *
	 * @return Placement[]
	 */
	private function get_all_placements() {
		static $placements;
		if ( null !== $placements ) {
			return $placements;
		}
		$placements = wp_advads_get_all_placements();

		return $placements;
	}

	/**
	 * Register relevant hooks
	 */
	public function init() {
		$placements = $this->get_all_placements();

		foreach ( $placements as $placement ) {
			if ( ! $placement->is_type( 'buddypress' ) ) {
				continue;
			}
			$hook = self::get_hook_from_placement_options( $placement );
			add_action( $hook, [ $this, 'execute_hook' ] );
		}
	}

	/**
	 * Execute frontend hooks
	 */
	public function execute_hook() {
		$placements = $this->get_all_placements();

		// Look for the current hook in the placements.
		$hook = current_filter();

		foreach ( $placements as $id => $placement ) {
			$hook_from_option = self::get_hook_from_placement_options( $placement );

			if ( ! $placement->is_type( 'buddypress' ) || $hook !== $hook_from_option ) {
				continue;
			}

			$index      = Max( 1, (int) $placement->get_prop( 'pro_buddypress_pages_index' ) );
			$did_action = did_action( $hook );

			if ( $did_action !== $index && ( $placement->get_prop( 'hook_repeat' ) || 0 !== $did_action % $index ) ) {
				continue;
			}

			if ( ! self::is_legacy_theme() && $placement->get_prop( 'activity_type' ) && ! $this->is_activity_type( $placement->get_prop( 'activity_type' ) ) ) {
				continue;
			}

			the_ad_placement( $id );
		}
	}

	/**
	 * Add visitor condition for BuddyPress profile fields
	 *
	 * @param array $conditions visitor conditions of the main plugin.
	 *
	 * @return array $conditions new global visitor conditions
	 */
	public function visitor_conditions( $conditions ) {
		// Stop if BuddyPress isn't activated.
		if ( ! class_exists( 'BuddyPress', false ) || ! function_exists( 'bp_profile_get_field_groups' ) ) {
			return $conditions;
		}

		$conditions['buddypress_profile_field'] = [
			'label'        => __( 'BuddyPress profile field', 'advanced-ads-pro' ),
			'description'  => __( 'Display ads based on BuddyPress profile fields.', 'advanced-ads-pro' ),
			'metabox'      => [ self::class, 'xprofile_metabox' ],
			'check'        => [ self::class, 'check_xprofile' ],
			'passive_info' => [
				'hash_fields' => 'field',
				'remove'      => 'login',
				'function'    => [ self::class, 'get_passive' ],
			],
		];

		// Update condition labels when BuddyBoss is used.
		if ( self::is_buddyboss() ) {
			$conditions['buddypress_profile_field']['label']       = __( 'BuddyBoss profile field', 'advanced-ads-pro' );
			$conditions['buddypress_profile_field']['description'] = __( 'Display ads based on BuddyBoss profile fields.', 'advanced-ads-pro' );
		}

		return $conditions;
	}

	/**
	 * Frontend check for the xprofile condition
	 *
	 * @param array $options condition options.
	 *
	 * @return bool
	 */
	public static function check_xprofile( $options = [] ) {
		if ( ! isset( $options['operator'] ) || ! isset( $options['value'] ) || ! isset( $options['field'] ) ) {
			return true;
		}
		$user_id  = get_current_user_id();
		$operator = $options['operator'];
		$value    = trim( $options['value'] );
		$field    = (int) $options['field'];
		if ( ! $user_id ) {
			return true;
		}

		$profile = self::get_profile_field_data( $field, $user_id );

		$trimmed_options = [
			'operator' => $operator,
			'value'    => $value,
		];

		if ( is_array( $profile ) ) {
			// Multi fields (checkboxes, dropdowns, etc).
			$positive_operator = in_array( $options['operator'], [ 'contain', 'start', 'end', 'match', 'regex' ], true );

			if ( ! $profile ) {
				return ! $positive_operator;
			}

			foreach ( $profile as $profile_item ) {
				$condition = \Advanced_Ads_Visitor_Conditions::helper_check_string( $profile_item, $trimmed_options );
				if (
					// If operator is positive, check if at least one string returns `true`.
					( $positive_operator && $condition )
					// If operator is negative, check if all strings return `true`.
					|| ( ! $positive_operator && ! $condition )
				) {
					break;
				}
			}

			return $condition;
		}

		// Single fields.
		return \Advanced_Ads_Visitor_Conditions::helper_check_string( $profile, $trimmed_options );
	}

	/**
	 * Get information to use in passive cache-busting.
	 *
	 * @param array $options condition options.
	 */
	public static function get_passive( $options = [] ) {
		if ( ! isset( $options['field'] ) ) {
			return;
		}
		$field = (int) $options['field'];

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		$profile = self::get_profile_field_data( $field, $user_id );

		return [
			'field' => $options['field'],
			'data'  => $profile,
		];
	}

	/**
	 * Get profile field data.
	 *
	 * @param int $field   Field ID.
	 * @param int $user_id ID of the user to get field data for.
	 *
	 * @return string[]|string
	 */
	private static function get_profile_field_data( $field, $user_id ) {
		if (
			! function_exists( 'bp_get_member_type' )
			|| ! function_exists( 'bp_get_profile_field_data' )
		) {
			return [];
		}

		// Process the "membertypes" field (BuddyBoss).
		if (
			function_exists( 'bp_get_xprofile_member_type_field_id' )
			&& \bp_get_xprofile_member_type_field_id() === $field
			&& function_exists( 'bp_member_type_post_by_type' )
		) {
			$member_types = \bp_get_member_type( $user_id, false );

			if ( ! is_array( $member_types ) ) {
				return [];
			}

			return array_map(
				function ( $member_type ) {
					return \bp_member_type_post_by_type( $member_type );
				},
				$member_types
			);
		}

		// Process fields other than the "membertypes".
		return \bp_get_profile_field_data(
			[
				'field'   => $field,
				'user_id' => $user_id,
			]
		);
	}

	/**
	 * Render xprofile visitor condition option
	 *
	 * @param array  $options   condition options.
	 * @param int    $index     index of the option.
	 * @param string $form_name name of the form.
	 */
	public static function xprofile_metabox( $options, $index = 0, $form_name = '' ) {
		if ( ! isset( $options['type'] ) || '' === $options['type'] ) {
			return;
		}

		$type_options = \Advanced_Ads_Visitor_Conditions::get_instance()->conditions;

		if ( ! isset( $type_options[ $options['type'] ] ) ) {
			return;
		}

		$groups             = \bp_profile_get_field_groups();
		$name               = \Advanced_Ads_Pro_Module_Advanced_Visitor_Conditions::get_form_name_with_index( $form_name, $index );
		$value              = isset( $options['value'] ) ? $options['value'] : '';
		$field              = isset( $options['field'] ) ? (int) $options['field'] : -1;
		$value              = $options['value'] ?? '';
		$operator           = $options['operator'] ?? 'is_equal';
		$current_field_type = self::get_current_field_type( $field );

		require AA_PRO_ABSPATH . 'modules/buddypress/views/xprofile-condition.php';
	}

	/**
	 * Get current field type.
	 *
	 * @param int $field Field ID.
	 *
	 * @return string
	 */
	private static function get_current_field_type( $field ) {
		if ( ! function_exists( 'bp_get_xprofile_member_type_field_id' ) ) {
			return self::FIELD_TEXTBOX;
		}

		if ( \bp_get_xprofile_member_type_field_id() === $field ) {
			return self::FIELD_MEMBERTYPES;
		}

		$groups = \bp_profile_get_field_groups();

		return $groups[0]->fields[0]->type ?? self::FIELD_TEXTBOX;
	}

	/**
	 * Add display condition for BuddyBoss groups.
	 *
	 * @param array $conditions Display conditions of the main plugin.
	 *
	 * @return array $conditions New display conditions.
	 */
	public function display_conditions( $conditions ) {
		// Stop if BuddyBoss isn't activated.
		if ( ! class_exists( 'BuddyPress', false ) || ! function_exists( 'groups_get_groups' ) ) {
			return $conditions;
		}

		$conditions['buddypress_group'] = [
			'label'       => __( 'BuddyPress group', 'advanced-ads-pro' ),
			'description' => __( 'Display ads based on existing BuddyPress groups.', 'advanced-ads-pro' ),
			'metabox'     => [ self::class, 'group_metabox' ],
			'check'       => [ self::class, 'group_check' ],
			'options'     => [
				'global' => false,
			],
			'helplink'    => 'https://wpadvancedads.com/manual/buddyboss-ads/?utm_source=advanced-ads?utm_medium=link&utm_campaign=condition-buddyboss-group',
		];

		if ( self::is_buddyboss() ) {
			$conditions['buddypress_group']['label']       = __( 'BuddyBoss group', 'advanced-ads-pro' );
			$conditions['buddypress_group']['description'] = __( 'Display ads based on existing BuddyBoss groups.', 'advanced-ads-pro' );
		}

		return $conditions;
	}

	/**
	 * Callback to display metabox for the BuddyBoss group condition.
	 *
	 * @param array  $options   Options of the condition.
	 * @param int    $index     Index of the condition.
	 * @param string $form_name Name of the form, falls back to class constant.
	 */
	public static function group_metabox( $options, $index = 0, $form_name = '' ) {
		if ( ! isset( $options['type'] ) || '' === $options['type'] ) {
			return;
		}

		$type_options = \Advanced_Ads_Display_Conditions::get_instance()->conditions;

		if ( ! isset( $type_options[ $options['type'] ] ) ) {
			return;
		}

		// Get values and select operator based on previous settings.
		$operator = ( isset( $options['operator'] ) && 'is_not' === $options['operator'] ) ? 'is_not' : 'is';
		$values   = ( isset( $options['value'] ) && is_array( $options['value'] ) ) ? array_map( 'absint', $options['value'] ) : [];

		// Form name basis.
		$name = \Advanced_Ads_Display_Conditions::get_form_name_with_index( $form_name, $index );
		$rand = md5( $name );

		// Load operator template.
		include ADVADS_ABSPATH . 'admin/views/conditions/condition-operator.php';

		$groups = self::get_buddypress_group_list();
		include AA_PRO_ABSPATH . 'modules/buddypress/views/display-condition-group.php';

		include ADVADS_ABSPATH . 'admin/views/conditions/not-selected.php';
		?>
		<p class="description">
			<?php esc_html_e( 'Display ads based on existing BuddyBoss groups.', 'advanced-ads-pro' ); ?>
			<a href="https://wpadvancedads.com/manual/buddyboss-ads/?utm_source=advanced-ads?utm_medium=link&utm_campaign=condition-buddyboss-group" class="advads-manual-link" target="_blank">
				<?php esc_html_e( 'Manual', 'advanced-ads-pro' ); ?>
			</a>
		</p>
		</div>
		<?php
	}

	/**
	 * Get the list of BuddyBoss groups.
	 *
	 * @return array.
	 */
	public static function get_buddypress_group_list() {
		$list   = [];
		$groups = \groups_get_groups( [ 'per_page' => -1 ] );

		if ( ! isset( $groups['groups'] ) || ! is_array( $groups['groups'] ) ) {
			return $list;
		}

		foreach ( $groups['groups'] as $group ) {
			if ( isset( $group->id, $group->name ) ) {
				$list[ $group->id ] = $group->name;
			}
		}

		return $list;
	}

	/**
	 * Check BuddyBoss group display condition in frontend.
	 *
	 * @param array $options options of the condition.
	 *
	 * @return bool True if can be displayed.
	 */
	public static function group_check( $options = [] ) {
		if ( ! isset( $options['value'] ) || ! is_array( $options['value'] ) || ! function_exists( 'bp_get_current_group_id' ) ) {
			return true;
		}

		$operator = isset( $options['operator'] ) && 'is_not' === $options['operator'] ? 'is_not' : 'is';

		return \Advanced_Ads_Display_Conditions::can_display_ids( \bp_get_current_group_id(), $options['value'], $operator );
	}

	/**
	 * Check if we are using BuddyPress legacy theme
	 *
	 * @return bool 1 if the site uses the legacy theme
	 */
	public static function is_legacy_theme() {
		return function_exists( 'bp_get_theme_package_id' ) && 'legacy' === \bp_get_theme_package_id();
	}

	/**
	 * Return the hook from the selected option
	 * the legacy method used another format, the new version stores the hooks in the option
	 *
	 * @param Placement $placement the placement.
	 *
	 * @return string hook name
	 */
	public static function get_hook_from_placement_options( $placement ) {
		$hook = $placement->get_prop( 'buddypress_hook' ) ?? 'bp_after_activity_entry';
		if ( empty( $hook ) ) {
			return 'bp_after_activity_entry';
		}

		// This accounts for previous versions of the add-on.
		return ( 'bp_' !== substr( $hook, 0, 3 ) )
			? str_replace( ' ', '_', 'bp_' . $hook )
			: $hook;
	}

	/**
	 * Check if BuddyBoss is installed instead of BuddyPress
	 *
	 * @return bool true if BuddyBoss is installed and used instead of BuddyPress
	 */
	public static function is_buddyboss() {
		return defined( 'BP_PLATFORM_VERSION' );
	}

	/**
	 * Check if passed activity type matches current activity type.
	 *
	 * @param string $activity_type Activity type to check.
	 *
	 * @return bool
	 */
	private function is_activity_type( $activity_type ) {
		switch ( $activity_type ) {
			case 'sitewide':
				return ! function_exists( 'bp_is_activity_directory' ) || \bp_is_activity_directory();
			case 'group':
				return ! function_exists( 'bp_is_group_activity' ) || \bp_is_group_activity();
			case 'member':
				return ! function_exists( 'bp_is_user_activity' ) || \bp_is_user_activity();
			default:
				return true;
		}
	}
}
