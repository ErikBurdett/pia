<?php
/**
 * Admin template file.
 * Brief description of the styles in this file
 *
 * @since   3.0.5
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro\Admin;

use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @param string $hook The current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || 'dashboard' === $screen->id ) {
			return;
		}

		wp_advads_pro()->registry->enqueue_style( 'admin-styles' );
	}
}
