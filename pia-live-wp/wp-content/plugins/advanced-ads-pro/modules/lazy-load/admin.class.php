<?php //phpcs:ignoreFile

use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\WordPress;

class Advanced_Ads_Pro_Module_Lazy_Load_Admin {

	public function __construct() {
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ] );

		// Render lazy load option.
		$options = Advanced_Ads_Pro::get_instance()->get_options();
		if ( empty( $options['lazy-load']['enabled'] ) ) {
			return;
		}
		add_action( 'advanced-ads-placement-options-after', [ $this, 'render_lazy_load_option' ], 10, 2 );
	}

	public function settings_init() {
		// add new section
		add_settings_field(
			'module-lazy-load',
			__( 'Lazy Loading', 'advanced-ads-pro' ),
			[ $this, 'render_settings' ],
			Advanced_Ads_Pro::OPTION_KEY . '-settings',
			Advanced_Ads_Pro::OPTION_KEY . '_modules-enable'
		);
	}

	public function render_settings() {
		include dirname( __FILE__ ) . '/views/settings.php';
	}

	/**
	 * Render lazy load option.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function render_lazy_load_option( $placement_slug, $placement ) {
		$data         = $placement->get_data();
		$options      = Advanced_Ads_Pro::get_instance()->get_options();
		$type_options = $placement->get_type_object()->get_options();

		if ( ! empty( $type_options['show_lazy_load'] ) ) {
			$checked = 'enabled' === $placement->get_prop( 'lazy_load' ) ? 'enabled' : 'disabled';
			$cb_off  = empty( $options['cache-busting']['enabled'] ) || ( isset( $data['cache-busting'] ) && Advanced_Ads_Pro_Module_Cache_Busting::OPTION_OFF === $data['cache-busting'] );

			ob_start();
			require dirname( __FILE__ ) . '/views/setting_lazy_load.php';
			$option_content = ob_get_clean();

			if ( $cb_off ) {
				$cache_busting_text = sprintf(
					'%1s (<a href="%2s" target="_blank">%3s</a>)',
					__( 'Cache Busting needs to be enabled', 'advanced-ads-pro' ),
					esc_url( get_admin_url('/','admin.php?page=advanced-ads-settings#top#pro') ),
					__( 'Settings', 'advanced-ads-pro' )
				);
			}

			WordPress::render_option(
				'placement-lazy-load',
				__( 'Lazy Loading', 'advanced-ads-pro' ),
				$option_content,
				sprintf(
					"%1s <br> %2s",
					__( 'Prevent ads from getting loaded before they appear in the visitor’s visible area.', 'advanced-ads-pro' ),
					$cache_busting_text ?? ''
				)
			);
		}
	}
}
