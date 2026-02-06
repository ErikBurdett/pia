<?php // phpcs:ignore WordPress.Files.FileName

use AdvancedAds\Utilities\WordPress;

/**
 * Placement conditions administration.
 */
class Advanced_Ads_Pro_Module_Placement_Conditions_Admin {
	/**
	 * The Constructor.
	 */
	public function __construct() {
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'render_conditions_for_placements' ], 10, 2 );
	}

	/**
	 * Render display and visitor condition for placement.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function render_conditions_for_placements( $placement_slug, $placement ) {
		if (
			! method_exists( 'Advanced_Ads_Display_Conditions', 'render_condition_list' )
			|| ! method_exists( 'Advanced_Ads_Visitor_Conditions', 'render_condition_list' )
		) {
			return;
		}

		$type_options = $placement->get_type_object()->get_options();

		if ( ! isset( $type_options['placement-display-conditions'] ) || $type_options['placement-display-conditions'] ) {
			$set_conditions = $placement->get_display_conditions();

			$list_target = 'advads-placement-condition-list-' . $placement_slug;
			$form_name   = 'advads[placements][options][display]';

			ob_start();

			if ( ! empty( $type_options['placement-display-conditions'] ) ) {
				// Render only specific conditions.
				$options['in'] = $type_options['placement-display-conditions'];
			} else {
				$options['in'] = 'global';
			}

			Advanced_Ads_Display_Conditions::render_condition_list( $set_conditions, $list_target, $form_name, $options );
			$conditions = ob_get_clean();

			WordPress::render_option(
				'placement-display-conditions',
				__( 'Display Conditions', 'advanced-ads-pro' ),
				$conditions
			);
		}

		if ( ! isset( $type_options['placement-visitor-conditions'] ) || $type_options['placement-visitor-conditions'] ) {

			$set_conditions = $placement->get_visitor_conditions();

			$list_target = 'advads-placement-condition-list-visitor-' . $placement_slug;
			$form_name   = 'advads[placements][options][visitors]';

			ob_start();
			Advanced_Ads_Visitor_Conditions::render_condition_list( $set_conditions, $list_target, $form_name );
			$conditions = ob_get_clean();

			WordPress::render_option(
				'placement-visitor-conditions',
				__( 'Visitor Conditions', 'advanced-ads-pro' ),
				$conditions
			);
		}
	}
}
