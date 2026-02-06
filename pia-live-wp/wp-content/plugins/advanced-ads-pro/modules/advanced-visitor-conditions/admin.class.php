<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Admin class for the Advanced Ads Pro Advanced Visitor Conditions module.
 *
 * @package AdvancedAds\Pro\Modules\Visitor_Conditions
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

/**
 * Class Visitor_Conditions
 */
class Advanced_Ads_Pro_Module_Advanced_Visitor_Conditions_Admin {

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
			'module-advanced-visitor-conditions',
			__( 'Advanced visitor conditions', 'advanced-ads-pro' ),
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
	public function render_settings(): void {
		include_once __DIR__ . '/views/settings.php';
	}
}
