<?php // phpcs:ignoreFile

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Utilities\WordPress;

class Advanced_Ads_Pro_Module_Grids_Admin {

	public function __construct() {
		// add new ad group type
		add_action( 'advanced-ads-group-form-options', [ $this, 'group_options' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_scripts' ], 10 );
	}

	/**
	 * render group options for grids
	 *
	 * @param Group $group Group instance.
	 */
	public function group_options( Group $group ) {
		$options = $group->get_data();
		$options = $options['options'] ?? [];

		$columns               = absint( $options['grid']['columns'] ?? 3 );
		$rows                  = absint( $options['grid']['rows'] ?? 2 );
		$inner_margin          = isset( $options['grid']['inner_margin'] ) ? min( absint( $options['grid']['inner_margin'] ), 50 ) : 3;
		$min_width             = absint( $options['grid']['min_width'] ?? 250 );
		$full_width_breakpoint = absint( $options['grid']['full_width_breakpoint'] ?? 0 );
		$random                = isset( $options['grid']['random'] ) ? 1 : 0;

		// Size.
		ob_start();
		include dirname( __FILE__ ) . '/views/grid-option-size.php';
		$option_content = ob_get_clean();
		WordPress::render_option(
			'group-pro-grid-size advads-group-type-grid',
			__( 'Size', 'advanced-ads-pro' ),
			$option_content );

		// margin
		ob_start();
		include dirname( __FILE__ ) . '/views/grid-option-margin.php';
		$option_content = ob_get_clean();
		WordPress::render_option(
			'group-pro-grid-margin advads-group-type-grid',
			__('Inner margin', 'advanced-ads-pro' ),
			$option_content );

		// min width
		WordPress::render_option(
			'group-pro-grid-width advads-group-type-grid',
			__('Min. width', 'advanced-ads-pro' ),
			'<input style="width:4em;" type="number" min="0" name="advads-groups['.$group->get_id().'][options][grid][min_width]" value="'.$min_width.'"/> px',
			__( 'Minimum width of a column in the grid.', 'advanced-ads-pro' ) );

		// full width on mobile
		WordPress::render_option(
			'group-pro-grid-mobile advads-group-type-grid',
			__('Full width', 'advanced-ads-pro' ),
			'<input style="width:4em;" type="number" min="0" name="advads-groups['.$group->get_id().'][options][grid][full_width_breakpoint]" value="'.$full_width_breakpoint.'"/> px',
			__( 'On browsers smaller than this, the columns show in full width below each other.', 'advanced-ads-pro' )
			. ' ' . __( 'Set to 0 to disable this feature.', 'advanced-ads-pro' ) );

		// random
		ob_start();
		?><input type="checkbox" name="advads-groups[<?php echo $group->get_id(); ?>][options][grid][random]"
		<?php if ($random) : ?>
		    checked = "checked"
		<?php endif; ?>
		/><?php
		$option_content = ob_get_clean();
		WordPress::render_option(
			'group-pro-grid-random advads-group-type-grid',
			__('Random order', 'advanced-ads-pro' ),
			$option_content );

	}

	/**
	 * load admin scripts needed for flash files
	 */
	public function load_admin_scripts() {

		// load only on ad group page
		$screen = get_current_screen();

		if( isset( $screen->id ) && 'advanced-ads_page_advanced-ads-groups' === $screen->id ) {
			wp_enqueue_script( 'advanced-ads-pro-grid-admin-group-script', plugins_url( 'assets/js/admin.js', __FILE__ ), ['jquery'], AAP_VERSION );
		}
	}
}
