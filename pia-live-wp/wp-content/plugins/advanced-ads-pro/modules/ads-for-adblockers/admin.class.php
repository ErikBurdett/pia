<?php // phpcs:ignore WordPress.Files.FileName
/**
 * Admin class for the Ads for Adblockers module.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

use AdvancedAds\Options;
use AdvancedAds\Constants;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Utilities\WordPress;
use AdvancedAds\Importers\XML_Importer;

/**
 * Admin class
 */
class Advanced_Ads_Pro_Module_Ads_For_Adblockers_Admin {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'advanced-ads-settings-init', [ $this, 'settings_init' ] );

		if ( ! Options::instance()->get( 'adblocker.ads-for-adblockers.enabled' ) ) {
			return;
		}

		add_filter( 'advanced-ads-import-placement', [ $this, 'import_placement' ], 10, 2 );
		add_action( 'advanced-ads-placement-options-after-advanced', [ $this, 'add_placement_setting' ], 10, 2 );
	}

	/**
	 * Initializes the settings
	 *
	 * @return void
	 */
	public function settings_init(): void {
		add_settings_field(
			'module-ads-for-adblockers',
			__( 'Ads for ad blockers', 'advanced-ads-pro' ),
			[ $this, 'render_settings' ],
			ADVADS_SETTINGS_ADBLOCKER,
			'advanced_ads_adblocker_setting_section'
		);
	}

	/**
	 * Render 'Ads For Adblocker' settings
	 *
	 * @return void
	 */
	public function render_settings(): void {
		$module_enabled    = Options::instance()->get( 'adblocker.ads-for-adblockers.enabled' );
		$cb_dashicon_class = ! empty( Advanced_Ads_Pro::get_instance()->get_options()['cache-busting']['enabled'] ) ? 'dashicons-yes advads-color-green' : 'dashicons-no color-red';
		$ab_dashicon_class = Options::instance()->get( 'adblocker.use-adblocker' ) ? 'dashicons-yes advads-color-green' : 'dashicons-no color-red';

		include_once __DIR__ . '/views/settings.php';
	}

	/**
	 * Render alternative item option.
	 *
	 * @param string    $placement_slug Placement id.
	 * @param Placement $placement      Placement instance.
	 */
	public function add_placement_setting( $placement_slug, $placement ) {
		$type_option = $placement->get_type_object()->get_options();
		if ( isset( $type_option['placement-item-alternative'] ) && ! $type_option['placement-item-alternative'] ) {
			return;
		}

		$options        = Advanced_Ads_Pro::get_instance()->get_options();
		$items          = $this->items_for_select();
		$messages       = $this->get_messages( $placement );
		$placement_data = $placement->get_data();
		$cb_off         = empty( $options['cache-busting']['enabled'] ) || ( isset( $placement_data['cache-busting'] ) && Advanced_Ads_Pro_Module_Cache_Busting::OPTION_OFF === $placement_data['cache-busting'] );

		ob_start();
		include __DIR__ . '/views/placement-item.php';
		$item_option_content = ob_get_clean();

		$ad_blocker_description = sprintf(
			'%1s. %2s (<a href="%3s" target="_blank">%4s</a>)',
			__( 'Displayed to visitors with an ad blocker', 'advanced-ads-pro' ),
			__( 'Cache Busting and Ad blocker disguise need to be enabled', 'advanced-ads-pro' ),
			esc_url( get_admin_url( '/', 'admin.php?page=advanced-ads-settings#top#pro' ) ),
			__( 'Settings', 'advanced-ads-pro' )
		);

		WordPress::render_option(
			'placement-item-alternative',
			__( 'Ad blocker item', 'advanced-ads-pro' ),
			$item_option_content,
			$ad_blocker_description
		);
	}

	/**
	 * Get items for item select field.
	 *
	 * @return array $select Items for select field.
	 */
	private function items_for_select() {
		static $select = null;

		// Check if result was cached.
		if ( null !== $select ) {
			return $select;
		}

		$select = [];
		// Load all ads.
		$ads = wp_advads_ad_query(
			[
				'order'   => 'ASC',
				'orderby' => 'title',
			]
		)->posts;

		foreach ( $ads as $_ad ) {
			$ad = wp_advads_get_ad( $_ad->ID );
			if ( $ad->is_type( [ 'plain', 'content', 'image' ] ) ) {
				$select['ads'][ Constants::ENTITY_AD . '_' . $_ad->ID ] = $_ad->post_title;
			}
		}

		return $select;
	}

	/**
	 * Get messages related to selected alternative item.
	 *
	 * @param Placement $placement Placement instance.
	 *
	 * @return array $messages Array of strings.
	 */
	private function get_messages( $placement ) {
		$messages = [];

		if ( $placement ) {
			$ad = Advanced_Ads_Pro_Module_Ads_For_Adblockers::get_item_for_adblocker( $placement );
			if ( $ad ) {
				$content = $ad->output();

				if ( preg_match( '/<script[^>]+src=[\'"]/is', $content ) ) {
					$messages[] .= __( 'The chosen ad contains a reference to an external .js file', 'advanced-ads-pro' );
				}
			}
		}

		return $messages;
	}

	/**
	 * Set an ad for adblocker during the import of a placement.
	 *
	 * @param array        $placement Placement data.
	 * @param XML_Importer $import    Import instance.
	 *
	 * @return array $placement
	 */
	public function import_placement( $placement, XML_Importer $import ) {
		if ( ! empty( $placement['options']['item_adblocker'] ) ) {
			$_item = explode( '_', $placement['options']['item_adblocker'] );
			if ( ! empty( $_item[1] ) ) {
				$found                                  = $import->search_item( $_item[1], $_item[0] );
				$placement['options']['item_adblocker'] = $found ? $_item[0] . '_' . $found : '';
			}
		}
		return $placement;
	}
}
