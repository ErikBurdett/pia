<?php
/**
 * Responsive Ads Admin.
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

namespace Advanced_Ads_Pro\Module\Responsive_Ads;

use Advanced_Ads_Pro;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin {

	/**
	 * Hook into WordPress.
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-settings-init', [ $this, 'add_settings' ] );
	}

	/**
	 * Add settings for module
	 *
	 * @return void
	 */
	public function add_settings() {
		$section_id = 'advanced_ads_responsive_setting_section';

		add_settings_section(
			$section_id,
			__( 'Responsive Ads', 'advanced-ads-pro' ),
			null,
			Advanced_Ads_Pro::OPTION_KEY . '-settings'
		);

		add_settings_field(
			'responsive-images',
			esc_html__( 'Responsive Image Ads', 'advanced-ads-pro' ),
			[ $this, 'render_setting_responsive_image_ads' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			$section_id
		);

		add_settings_field(
			'reload-ads-on-resize',
			esc_html__( 'Reload ads on resize', 'advanced-ads-pro' ),
			[ $this, 'render_settings_reload_ads_on_resize' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			$section_id
		);

		add_settings_field(
			'fallback-width',
			esc_html__( 'Fallback width', 'advanced-ads-pro' ),
			[ $this, 'render_settings_fallback_width' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			$section_id
		);
	}

	/**
	 * Render responsive image settings field
	 */
	public function render_setting_responsive_image_ads() {
		$options          = Advanced_Ads_Pro::get_instance()->get_options();
		$force_responsive = isset( $options['responsive-ads']['force-responsive-images'] );

		require $this->get_view_path() . '/setting_responsive_images.php';
	}

	/**
	 * Render setting to reload ads when screen resizes.
	 */
	public function render_settings_reload_ads_on_resize() {
		$options                   = Advanced_Ads_Pro::get_instance()->get_options();
		$cache_busting_enabled     = isset( $options['cache-busting']['enabled'] ) && boolval( $options['cache-busting']['enabled'] );
		$reload_ads_option_enabled = ! empty( $options['responsive-ads']['reload-ads-on-resize'] );

		require $this->get_view_path() . '/setting_reload_ads.php';
	}

	/**
	 * Render setting to set fallback width.
	 */
	public function render_settings_fallback_width() {
		$options = Advanced_Ads_Pro::get_instance()->get_options();
		$width   = ! empty( $options['responsive-ads']['fallback-width'] ) ? absint( $options['responsive-ads']['fallback-width'] ) : 768;
		require $this->get_view_path() . '/setting_fallback_width.php';
	}

	/**
	 * Get view path for module
	 *
	 * @return string View folder path.
	 */
	private function get_view_path() {
		return AAP_BASE_PATH . 'modules/responsive-ads/views';
	}

	/**
	 * Returns true if the Responsive addon is active.
	 * If the loading order of the plugins is not default,
	 * AAR_BASE_PATH could be undefined, even though the Responsive add-on is still installed.
	 * This is an approximation to that issue. If a user has renamed the plugin file, this will also fail.
	 *
	 * @return bool
	 */
	public function is_responsive_active(): bool {
		$plugins = array_filter(
			apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
			static function ( $plugin ) {
				$needle = 'responsive-ads.php';
				$len    = strlen( $needle );

				return substr_compare( $plugin, $needle, -$len, $len ) === 0;
			}
		);

		$active_by_string = ! empty( $plugins );

		return defined( 'AAR_BASE_PATH' ) || $active_by_string;
	}

	/**
	 * Returns true if the Responsive addon is deprecated.
	 *
	 * @return true
	 */
	public function is_responsive_outdated(): bool {
		return $this->is_responsive_active() && ! defined( 'AAR_AMP_ADSENSE_ONLY' );
	}
}
