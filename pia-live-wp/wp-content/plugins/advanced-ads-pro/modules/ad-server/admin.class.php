<?php // phpcs:ignoreFile

use AdvancedAds\Utilities\WordPress;

/**
 * Allow serving ads on external URLs.
 *
 * Class Advanced_Ads_Pro_Module_Ad_Server_Admin
 */
class Advanced_Ads_Pro_Module_Ad_Server_Admin {

	/**
	 * Advanced_Ads_Pro_Module_Ad_Server_Admin constructor.
	 */
	public function __construct() {

		// Add settings section to allow module enabling.
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ] );

		// Check if the module was enabled.
		$options = Advanced_Ads_Pro::get_instance()->get_options();
		if ( empty( $options['ad-server']['enabled'] ) ) {
			return;
		}

		// Show usage information under "show all options".
		add_filter( 'advanced-ads-placement-options-after-advanced', [ $this, 'add_placement_setting' ], 10, 2 );
	}

	/**
	 * Option to enable the Ad Server module.
	 */
	public function settings_init() {
		// Add new section.
		add_settings_field(
			'module-ad-server',
			__( 'Ad Server', 'advanced-ads-pro' ),
			[ $this, 'render_settings' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			Advanced_Ads_Pro::OPTION_KEY . '_modules-enable'
		);
	}

	/**
	 * Render Ad Server module option.
	 */
	public function render_settings() {

		$options           = Advanced_Ads_Pro::get_instance()->get_options();
		$module_enabled    = isset( $options['ad-server']['enabled'] ) && $options['ad-server']['enabled'];
		$embedding_url     = isset( $options['ad-server']['embedding-url'] ) ? $options['ad-server']['embedding-url'] : '';
		$block_no_referrer = ! empty( $options['ad-server']['block-no-referrer'] ); // True if option is set.

		include dirname( __FILE__ ) . '/views/module-settings.php';
	}

	/**
	 * Show usage information for the ad server
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function add_placement_setting( $placement_slug, $placement ) {
		if ( ! $placement->is_type( 'server' ) ) {
			return;
		}

		// Publically visible name of the placement. Defaults to the placement slug.
		$placement_options = $placement->get_data();
		$public_slug = ! empty( $placement_options['ad-server-slug'] ) ? sanitize_title( $placement_options['ad-server-slug'] ) : $placement_slug;

		ob_start();
		include dirname( __FILE__ ) . '/views/placement-settings.php';
		$slug_content = ob_get_clean();

		WordPress::render_option(
			'ad-server-usage',
			__( 'Public string', 'advanced-ads-pro' ),
			$slug_content
		);

		$options = Advanced_Ads_Pro::get_instance()->get_options();
		// Static URL used for the placement to deliver the content.
		$url = admin_url( 'admin-ajax.php' ) . '?action=aa-server-select&p=' . $public_slug;

		ob_start();
		include dirname( __FILE__ ) . '/views/placement-usage.php';
		$usage_content = ob_get_clean();

		WordPress::render_option(
			'ad-server-usage',
			__( 'Usage', 'advanced-ads-pro' ),
			$usage_content
		);
	}


}

