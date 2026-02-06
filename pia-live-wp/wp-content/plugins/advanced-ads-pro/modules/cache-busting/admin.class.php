<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Admin class for the Advanced Ads Pro Cache Busting module.
 *
 * @package AdvancedAds\Pro\Modules\Cache_Busting
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

/**
 * Class Cache_Busting
 */
class Advanced_Ads_Pro_Module_Cache_Busting_Admin {

	/**
	 * The constructor
	 */
	public function __construct() {
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ] );
	}

	/**
	 * Add settings for the module.
	 *
	 * @return void
	 */
	public function settings_init(): void {
		add_settings_field(
			'module-cache-busting',
			__( 'Cache Busting', 'advanced-ads-pro' ),
			[ $this, 'render_settings' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			Advanced_Ads_Pro::OPTION_KEY . '_modules-enable'
		);
	}

	/**
	 * Render the settings.
	 *
	 * @return void
	 */
	public function render_settings() {
		include_once __DIR__ . '/views/settings.php';
	}
}
