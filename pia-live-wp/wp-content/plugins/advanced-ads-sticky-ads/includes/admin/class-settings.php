<?php
/**
 * Admin Settings.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds\Admin;

use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings.
 */
class Settings implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ] );
	}

	/**
	 * Add settings to settings page
	 *
	 * @since 1.0.0
	 * @param string $hook settings page hook.
	 *
	 * @return void
	 */
	public function settings_init( $hook ): void {
		// Add new section.
		add_settings_section(
			'advanced_ads_sticky_setting_section',
			__( 'Sticky Ads', 'advanced-ads-sticky' ),
			[ $this, 'render_settings_section' ],
			$hook
		);

		// Add setting fields.
		add_settings_field(
			'use-js-lib',
			__( 'Check browser capability', 'advanced-ads-sticky' ),
			[ $this, 'render_settings_scroll' ],
			$hook,
			'advanced_ads_sticky_setting_section'
		);
	}

	/**
	 * Render advanced scroll method setting
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_section(): void {
		esc_html_e( 'Settings for the Sticky Ads add-on', 'advanced-ads-sticky' );
	}

	/**
	 * Render advanced scroll method setting
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_scroll(): void {
		$options              = wp_advads_stickyads()->get_options();
		$check_position_fixed = $options['sticky']['check-position-fixed'] ?? 0;

		include_once AA_STICKY_ADS_ABSPATH . 'views/admin/settings/general/activate-sticky-ads.php';
	}
}
