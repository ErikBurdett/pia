<?php
/**
 * Admin Placement Settings.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds\Admin;

use AdvancedAds\Utilities\WordPress;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Placement Settings.
 */
class Placement_Settings implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'placement_content' ], 10, 2 );
	}

	/**
	 * Add color picker script
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	public function admin_scripts(): void {
		$wp_screen = get_current_screen();

		if ( 'edit-advanced_ads_plcmnt' === $wp_screen->id ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			add_action( 'admin_footer', [ $this, 'color_picker_script' ] );
		}
	}

	/**
	 * Activate color picker fields
	 *
	 * @return void
	 */
	public function color_picker_script(): void {
		?>
		<script>
			jQuery( $ => {
				for ( const modal of document.getElementsByClassName( 'advads-modal' ) ) {
					modal.addEventListener( 'advads-modal-opened', e => {
						jQuery('.advads-sticky-bg-color-field').wpColorPicker();
					});
				}
			});
		</script>
		<?php
		// Check if the following code is included in the basic plugin.
		if ( 0 <= version_compare( ADVADS_VERSION, '1.19' ) ) {
			return;
		}

		if ( ! defined( 'AAP_VERSION' ) ) :
			?>
			<script>
			if ( localStorage.getItem( 'advads_frontend_element' )) {
				var placement = localStorage.getItem( 'advads_frontend_picker' );
				var id = 'advads-frontend-element-' + placement;
				jQuery( '[id="' + id + '"]' ).find( '.advads-frontend-element' ).val( localStorage.getItem( 'advads_frontend_element' ) );

				var action = localStorage.getItem( 'advads_frontend_action' );
				if (typeof(action) !== 'undefined') {
					var show_all_link = jQuery( 'a[data-placement="' + placement + '"]');
					Advanced_Ads_Admin.toggle_placements_visibility( show_all_link );
				}
				localStorage.removeItem( 'advads_frontend_action' );
				localStorage.removeItem( 'advads_frontend_element' );
				localStorage.removeItem( 'advads_frontend_picker' );
				localStorage.removeItem( 'advads_prev_url' );
			}
			jQuery('.advads-activate-frontend-picker').click(function( e ) {
				localStorage.setItem( 'advads_frontend_picker', jQuery( this ).data('placementid') );
				localStorage.setItem( 'advads_frontend_action', jQuery( this ).data('action') );
				localStorage.setItem( 'advads_prev_url', window.location );
				window.location = "<?php echo home_url(); // phpcs:ignore ?>";
			});
			</script>
			<?php
		endif;
	}

	/**
	 * Render sticky placement content
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 *
	 * @return void
	 */
	public function placement_content( $placement_slug, $placement ): void {
		$data        = $placement->get_data();
		$options     = $data['sticky'] ?? [];
		$option_name = 'advads[placements][options][sticky]';

		if ( $placement->is_type( [ 'sticky_header', 'sticky_footer' ] ) ) {
			ob_start();
			?>
			<input type="text" value="<?php echo esc_attr( $data['sticky_bg_color'] ?? '' ); ?>" class="advads-sticky-bg-color-field" name="advads[placements][options][sticky_bg_color]" />
			<p class="description">
				<?php esc_html_e( 'When selecting a background color, the sticky bar will cover the whole screen width.', 'advanced-ads-sticky' ); ?>
			</p>
			<?php
			$option_content = ob_get_clean();
			WordPress::render_option(
				'placement-sticky-background',
				__( 'background', 'advanced-ads-sticky' ),
				$option_content
			);

			include AA_STICKY_ADS_ABSPATH . 'views/admin/trigger.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/effects.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/close-button.php';

			$height = false;
			$width  = absint( $data['placement_width'] ?? 0 );
			include AA_STICKY_ADS_ABSPATH . 'views/admin/size.php';
		}

		if ( $placement->is_type( [ 'sticky_left_sidebar', 'sticky_right_sidebar' ] ) ) {
			include AA_STICKY_ADS_ABSPATH . 'views/admin/trigger.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/effects.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/vertical-center.php';

			ob_start();
			?>
			<div id="advads-frontend-element-<?php echo esc_attr( $placement_slug ); ?>">
				<input type="text" name="advads[placements][options][sticky_element]" value="<?php echo esc_attr( $data['sticky_element'] ?? '' ); ?>" class="advads-frontend-element"/>
				<button style="display:none; color: red;" type="button" class="advads-deactivate-frontend-picker button"><?php echo esc_html_x( 'stop selection', 'frontend picker', 'advanced-ads-sticky' ); ?></button>
				<button type="button" class="advads-activate-frontend-picker button" data-placementid="<?php echo esc_attr( $placement_slug ); ?>" data-action="edit-placement"><?php esc_html_e( 'select position', 'advanced-ads-sticky' ); ?></button>
			</div>
			<p class="description">
				<?php _e( 'Use <a href="https://api.jquery.com/category/selectors/" target="_blank">jQuery selectors</a> to select a custom parent element or if automatic wrapper detection doesn’t work, e.g. #container_id, .container_class', 'advanced-ads-sticky' ); // phpcs:ignore ?>
			</p>
			<?php
			$option_content = ob_get_clean();

			WordPress::render_option(
				'placement-sticky-element',
				__( 'parent element', 'advanced-ads-sticky' ),
				$option_content
			);

			include AA_STICKY_ADS_ABSPATH . 'views/admin/close-button.php';

			$width  = absint( $data['placement_width'] ?? 0 );
			$height = absint( $data['placement_height'] ?? 0 );
			include AA_STICKY_ADS_ABSPATH . 'views/admin/size.php';
		}

		if ( $placement->is_type( [ 'sticky_left_window', 'sticky_right_window' ] ) ) {
			include AA_STICKY_ADS_ABSPATH . 'views/admin/trigger.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/effects.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/vertical-center.php';
			include AA_STICKY_ADS_ABSPATH . 'views/admin/close-button.php';

			$width  = $data['placement_width'] ?? 0;
			$height = $data['placement_height'] ?? 0;
			include AA_STICKY_ADS_ABSPATH . 'views/admin/size.php';
		}
	}
}
