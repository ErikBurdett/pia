<?php
/**
 * Admin Admin.
 *
 * @package AdvancedAds\Slider
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.5.0
 */

namespace AdvancedAds\Slider\Admin;

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Framework\Utilities\Formatting;
use AdvancedAds\Utilities\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Admin.
 */
class Admin implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'advanced-ads-group-form-options', [ $this, 'group_options' ] );
	}

	/**
	 * Enqueue plugin admin script
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$screen = get_current_screen();

		if ( 'advanced-ads_page_advanced-ads-groups' === $screen->id ) {
			wp_enqueue_script( 'advads-slider-admin-script', AA_SLIDER_BASE_URL . 'assets/js/admin.js', [ 'jquery' ], AAS_VERSION ); // phpcs:ignore
		}
	}

	/**
	 * Render group options for slider
	 *
	 * @param Group $group Group instance.
	 *
	 * @return void
	 */
	public function group_options( Group $group ): void {
		$options = $group->get_data();
		$options = $options['options'] ?? [];
		$delay   = absint( $options['slider']['delay'] ?? 2000 );
		$random  = Formatting::string_to_bool( $options['slider']['random'] ?? false );

		// Delay.
		ob_start();
		?>
		<input type="number" name="advads-groups[<?php echo esc_attr( $group->get_id() ); ?>][options][slider][delay]" value="<?php echo esc_attr( $delay ); ?>"/>
		<?php
		$option_content = ob_get_clean();

		WordPress::render_option(
			'group-slider-delay advads-group-type-slider',
			__( 'Slide delay', 'slider-ads' ),
			$option_content,
			__( 'Pause for each ad slide in milliseconds', 'slider-ads' )
		);

		// Random.
		ob_start();
		?>
		<input type="checkbox" name="advads-groups[<?php echo esc_attr( $group->get_id() ); ?>][options][slider][random]"<?php checked( $random ); ?> />
		<?php
		$option_content = ob_get_clean();

		WordPress::render_option(
			'group-slider-random advads-group-type-slider',
			__( 'Random order', 'slider-ads' ),
			$option_content,
			__( 'Display ads in the slider in a random order', 'slider-ads' )
		);
	}
}
