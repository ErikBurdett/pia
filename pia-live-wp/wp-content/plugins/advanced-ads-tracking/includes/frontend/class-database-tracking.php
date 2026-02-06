<?php
/**
 * Frontend Database Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Database Tracking.
 */
class Database_Tracking implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-ad-output-ready', [ $this, 'track_on_output' ], 10, 2 );
	}

	/**
	 * Track impression on output.
	 *
	 * @since 1.0.0
	 *
	 * @param Ad     $ad     Ad instance.
	 * @param string $output The ad output string.
	 *
	 * @return void
	 */
	public function track_on_output( Ad $ad, $output ): void {
		$start_time    = microtime( true );
		$global_output = $ad->get_prop( 'global_output' ) ?? false;

		if ( ! $global_output || empty( $output ) || ! Tracking::has_ad_tracking_enabled( $ad ) ) {
			return;
		}

		// Do not track delayed ads when AJAX option enabled.
		if (
			( ! empty( $ad->get_prop( 'layer_placement.trigger' ) ) || ! empty( $ad->get_prop( 'sticky.trigger' ) ) )
			&& Helpers::has_delayed_ads()
		) {
			return;
		}

		Tracking::track_impression( $ad->get_id(), $start_time );
	}
}
