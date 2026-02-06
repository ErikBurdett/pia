<?php
/**
 * Admin WordPress Dashboard.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin WordPress Dashboard.
 */
class WordPress_Dashboard implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 10, 0 );
		add_action( 'advanced-ads-dashboard-performing-ads', [ $this, 'display_performing_ads' ] );
	}

	/**
	 * Enqueue styles and scripts for current screen
	 *
	 * @return void
	 */
	public function enqueue(): void {
		// Early bail!!
		$wp_screen = get_current_screen();
		if ( 'dashboard' !== $wp_screen->id ) {
			return;
		}

		wp_advads_tracking()->registry->enqueue_style( 'wp-dashboard' );
		wp_advads_tracking()->registry->enqueue_script( 'wp-dashboard' );
	}

	/**
	 * Render best performing ads.
	 *
	 * @return void
	 */
	public function display_performing_ads(): void {
		include ADVADS_ABSPATH . 'views/spinner.php';
		echo '<div id="advads-performing-container"></div>';
	}
}
