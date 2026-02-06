<?php
/**
 * Quick Edit.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Admin;

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Quick Edit.
 */
class Quick_Edit implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-quick-edit-ad-data', [ $this, 'add_quick_edit_ad_data' ], 10, 2 );
	}

	/**
	 * Add the quick edit ad data
	 *
	 * @param array $data Ad data.
	 * @param Ad    $ad   Ad.
	 *
	 * @return array
	 */
	public function add_quick_edit_ad_data( $data, $ad ) {
		$data['targetUrl']       = $ad->get_prop( 'url' );
		$data['cloakLink']       = $ad->get_prop( 'tracking.cloaking' );
		$data['targetWindow']    = $ad->get_prop( 'tracking.target' );
		$data['nofollow']        = $ad->get_prop( 'tracking.nofollow' );
		$data['sponsored']       = $ad->get_prop( 'tracking.sponsored' );
		$data['reportRecipient'] = $ad->get_prop( 'tracking.report-recip' );

		$data['trackingMethod']  = $ad->get_prop( 'tracking.enabled' );
		$data['trackingChoices'] = [
			'default'  => __( 'default (impressions & clicks)', 'advanced-ads-tracking' ),
			'disabled' => __( 'disabled', 'advanced-ads-tracking' ),
			'enabled'  => __( 'enabled', 'advanced-ads-tracking' ),
		];

		if ( Helpers::is_clickable_type( $ad->get_type() ) ) {
			$data['trackingChoices'] = array_merge(
				$data['trackingChoices'],
				[
					'clicks'      => __( 'clicks only', 'advanced-ads-tracking' ),
					'impressions' => __( 'impressions only', 'advanced-ads-tracking' ),
					'enabled'     => __( 'impressions & clicks', 'advanced-ads-tracking' ),
				]
			);
		}

		return $data;
	}
}
