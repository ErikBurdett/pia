<?php
/**
 * Update routine
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

use AdvancedAds\Options;

/**
 * Update something
 *
 * @since 2.26.0
 *
 * @return void
 */
function move_adblocker_settings(): void {
	$options = Advanced_Ads_Pro::get_instance()->get_options();

	if ( ! empty( $options['ads-for-adblockers']['enabled'] ) ) {
		Options::instance()->set( 'adblocker.ads-for-adblockers.enabled', true );
	}
}

move_adblocker_settings();
