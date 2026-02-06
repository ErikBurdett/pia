<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Admin class for the Advanced Ads Pro Click Fraud Protection module.
 *
 * @package AdvancedAds\Pro\Modules\Visitor_Conditions
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

/**
 * Class Advanced_Ads_Pro_Module_CFP_Admin
 */
class Advanced_Ads_Pro_Module_CFP_Admin {

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
			'module-cfp',
			__( 'Click Fraud Protection', 'advanced-ads-pro' ),
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
