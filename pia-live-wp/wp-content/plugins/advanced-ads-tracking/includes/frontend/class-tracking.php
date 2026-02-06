<?php
/**
 * Frontend Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Tracking.
 */
class Tracking implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Load tracking method for AJAX requests.
		if ( wp_doing_ajax() ) {
			$this->load_tracking_method();
		}

		add_action( 'wp', [ $this, 'load_tracking_method' ], 10 );
	}

	/**
	 * Load the scripts and hooks according to the tracking method
	 *
	 * @since 1.0.0
	 */
	public function load_tracking_method() {
		if ( Helpers::ignore_logged_in_user() ) {
			return;
		}

		$method = Helpers::get_tracking_method();

		if ( Conditional::is_amp() && 'onrequest' === $method ) {
			( new Database_Tracking() )->hooks();
			return;
		}

		if ( (bool) apply_filters( 'advanced-ads-tracking-load-header-scripts', true ) ) {
			( new Tracking_Scripts() )->hooks();
		}

		// If this is an ajax call and tracking is frontend, track the ads on shutdown.
		if ( 'frontend' === $method && wp_doing_ajax() ) {
			( new Ajax_Tracking() )->hooks();
		}

		// Database tracking method selected.
		if ( 'onrequest' === $method ) {
			( new Database_Tracking() )->hooks();
		}

		// Parallel analytics tracking && multi-site.
		if ( is_multisite() || 'ga' === $method || Helpers::is_forced_analytics() ) {
			( new Analytics_Tracking() )->hooks();
		}
	}
}
