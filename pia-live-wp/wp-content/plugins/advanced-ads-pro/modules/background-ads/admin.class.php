<?php // phpcs:ignoreFile

use AdvancedAds\Utilities\WordPress;

/**
 * Background ads placement module.
 */
class Advanced_Ads_Pro_Module_Background_Ads_Admin {

	/**
	 * Constructor. Register relevant hooks.
	 */
	public function __construct() {
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'placement_options' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'admin_footer', [ $this, 'inline_script' ] );
	}

	/**
	 * Render placement option.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function placement_options( $placement_slug, $placement ){
	    if ( $placement->is_type( 'background' ) ) {
			$data = $placement->get_data();
		    $bg_color = ( isset($data['bg_color']) ) ? $data['bg_color'] : '';
		    $option_content = '<input type="text" value="'. $bg_color .'" class="advads-bg-color-field" name="advads[placements][options][bg_color]"/>';
		    $description = __( 'Select a background color in case the background image is not high enough to cover the whole screen.', 'advanced-ads-pro' );
			WordPress::render_option(
				'placement-background-color',
				__( 'background', 'advanced-ads-pro' ),
				$option_content,
				$description );
	    }

	}

	/**
	 * add color picker script to placements page
	 *
	 * @since 1.8
	 */
	function admin_scripts() {
		$screen = get_current_screen();

		if ( ! function_exists( 'wp_advads' ) || 'edit-advanced_ads_plcmnt' !== $screen->id ) {
			return;
		};

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Add footer script on the placement screen
	 *
	 * @since 1.8
	 */
	public function inline_script() {
		if ( ! $this->is_placement_screen() ) {
			return;
		}
		?>
		<script>
			jQuery( $ => {
				for ( const modal of document.getElementsByClassName( 'advads-modal' ) ) {
					modal.addEventListener( 'advads-modal-opened', (e) => {
						jQuery( e.target ).find( '.advads-bg-color-field' ).wpColorPicker( {
							change: ( e, ui ) => {
								e.target.value = ui.color.toString();
								e.target.dispatchEvent( new Event( 'change' ) );
							},
							clear:  e => {
								if ( e.type === 'change' ) {
									return;
								}

								jQuery( e.target ).parent().find( '.advads-bg-color-field' )[0].dispatchEvent( new Event( 'change' ) );
							}
						} );
					} );
				}
			} );
		</script><?php
	}

	/**
	 * Whether we are on placement screen
	 *
	 * @return bool
	 */
	private function is_placement_screen() {
		static $is_placement_screen;

		if ( null === $is_placement_screen ) {
			$screen = get_current_screen();
			if ( ! $screen ) {
				return false;
			}
			$is_placement_screen = 'edit-advanced_ads_plcmnt' === $screen->id;
		}

		return $is_placement_screen;
	}
}
