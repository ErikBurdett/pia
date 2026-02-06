<?php
/**
 * Responsive Ads Frontend.
 *
 * @package    Advanced_Ads_Pro\Module
 * @subpackage Responsive_Ads
 * @author     Advanced Ads <info@wpadvancedads.com>
 */

namespace Advanced_Ads_Pro\Module\Responsive_Ads;

use Advanced_Ads_Pro;
use AdvancedAds\Utilities\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend
 */
class Frontend {

	/**
	 * Hook into WordPress.
	 */
	public function hooks(): void {
		$options = Advanced_Ads_Pro::get_instance()->get_options();

		if ( isset( $options['responsive-ads']['force-responsive-images'] ) && $options['responsive-ads']['force-responsive-images'] ) {
			add_filter( 'advanced-ads-ad-image-tag-style', [ $this, 'force_responsive_images' ] );
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 15 );
	}

	/**
	 * Force responsive image by adding styles
	 *
	 * @param string $image_styles Existing image styles.
	 *
	 * @return string New image styles
	 */
	public function force_responsive_images( $image_styles ) {
		return $image_styles . ' max-width: 100%; height: auto;';
	}

	/**
	 * Enqueue options.
	 *
	 * @return void
	 */
	public function register_scripts() {
		if ( Conditional::is_amp() ) {
			return;
		}

		if ( ! defined( 'ADVADS_SLUG' ) || defined( 'ADVANCED_ADS_RESPONSIVE_DISABLE_BROWSER_WIDTH' ) ) {
			return;
		}

		$options = Advanced_Ads_Pro::get_instance()->get_options();

		wp_localize_script(
			'advanced-ads-pro/cache_busting',
			'advanced_ads_responsive',
			[
				'reload_on_resize' => ! empty( $options['responsive-ads']['reload-ads-on-resize'] ) ? 1 : 0,
			]
		);
	}
}
