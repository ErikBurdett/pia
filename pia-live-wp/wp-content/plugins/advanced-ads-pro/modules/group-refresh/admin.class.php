<?php // phpcs:ignoreFile
/**
 * Advanced_Ads_Pro_Group_Refresh_Admin
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Utilities\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Refresh admin
 */
class Advanced_Ads_Pro_Group_Refresh_Admin {

	public function __construct() {
		add_action( 'advanced-ads-group-form-options', [ $this, 'add_group_refresh_options' ] );
	}

	/**
	 * Render group refresh options
	 *
	 * @param Group $group Group instance.
	 */
	public function add_group_refresh_options( Group $group ) {
		$data    = $group->get_data();
		$data    = $data['options'] ?? [];
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		$cb_module_enabled = ! empty( $options['cache-busting']['enabled'] );
		$enabled           = Advanced_Ads_Pro_Group_Refresh::is_enabled( $group );
		$interval          = ! empty( $data['refresh']['interval'] ) ? Advanced_Ads_Pro_Utils::absint( $data['refresh']['interval'], 1 ) : 2000;
		$show_warning      = false;

		if ( $cb_module_enabled && $enabled && function_exists( 'wp_advads_placements_by_item_id') ) {
			$show_warning = true;
			$placements   = wp_advads_placements_by_item_id( 'group_' . $group->get_id() );

			foreach ( $placements as $placement ) {
				$cache_busting = $placement->get_prop( 'cache-busting' ) ?? false;
				if (
					$cache_busting &&
					Advanced_Ads_Pro_Module_Cache_Busting::OPTION_OFF !== $cache_busting
				) {
					$show_warning = false;
					break;
				}
			}
		}

		ob_start();
		include 'views/settings_group_refresh.php';
		$option_content = ob_get_clean();

		WordPress::render_option(
			'group-pro-refresh advads-group-type-default advads-group-type-ordered',
			__( 'Refresh interval', 'advanced-ads-pro' ),
			$option_content
		);
	}
}
