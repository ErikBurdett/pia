<?php
/**
 * Frontend Tracking Scripts.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Utilities\Str;
use AdvancedAds\Tracking\Utilities\Data;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Tracking Scripts.
 */
class Tracking_Scripts implements Integration_Interface {

	/**
	 * Ads for which page query string should be transmitted.
	 *
	 * @var array
	 */
	protected $transmit_pageqs = [];

	/**
	 * Ad ids that should be tracked using JavaScript
	 *
	 * @var arr
	 */
	protected $ad_ids = [];

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 11 );
		add_action( 'advanced-ads-ad-output-ready', [ $this, 'collect_ad_id' ], 10, 3 );
		add_filter( 'advanced-ads-output-wrapper-options', [ $this, 'add_wrapper' ], 20, 2 );
		add_action( 'wp_footer', [ $this, 'output_ad_ids' ], PHP_INT_MAX - 1 );
	}

	/**
	 * Load header scripts (actually loads in footer).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( Conditional::is_amp() ) {
			return;
		}

		$deps           = [];
		$is_ga_tracking = Helpers::is_tracking_method( 'ga' ) || is_multisite() || Helpers::is_forced_analytics();

		if ( wp_script_is( 'advanced-ads-pro/cache_busting' ) ) {
			$deps[] = 'advanced-ads-pro/cache_busting';
		}

		wp_enqueue_script( 'advadsTrackingScript', AA_TRACKING_BASE_URL . 'assets/dist/tracking.js', $deps, AAT_VERSION, true );
		$deps[] = 'advadsTrackingScript';
		if ( $is_ga_tracking ) {
			wp_enqueue_script( 'advadsTrackingGAFront', AA_TRACKING_BASE_URL . 'assets/dist/ga-tracking.js', $deps, AAT_VERSION, true );
		}

		// Pass ajax_action name to script.
		wp_localize_script(
			'advadsTrackingScript',
			'advadsTracking',
			[
				'impressionActionName' => Constants::TRACK_IMPRESSION,
				'clickActionName'      => Constants::TRACK_CLICK,
				'targetClass'          => wp_advads()->get_frontend_prefix() . 'target',
				'blogId'               => get_current_blog_id(),
				'frontendPrefix'       => wp_advads()->get_frontend_prefix(),
			]
		);

		if ( $is_ga_tracking ) {
			wp_advads()->json->add(
				'tracking',
				[
					'googleEvents' => [
						/**
						 * Filters the Google Analytics 4 event name for ad impressions
						 *
						 * @param string $impression_name ad impression event name.
						 */
						'impression' => apply_filters( 'advanced-ads-tracking-ga-impression', 'advanced_ads_impression' ),

						/**
						 * Filters the Google Analytics 4 event name for clicks
						 *
						 * @param string $click_name ad click event name.
						 */
						'click'      => apply_filters( 'advanced-ads-tracking-ga-click', 'advanced_ads_click' ),
					],
				]
			);
		}

		// Delayed ads add-ons are available.
		if ( Helpers::has_delayed_ads() ) {
			wp_enqueue_script( 'advadsTrackingDelayed', AA_TRACKING_BASE_URL . 'assets/dist/delayed.js', array_merge( $deps, [ 'jquery' ] ), AAT_VERSION, true );
		}
	}

	/**
	 * Collect ad id, so that JavaScript can access it
	 *
	 * @param Ad     $ad     Ad instance.
	 * @param string $output The ad output string.
	 */
	public function collect_ad_id( Ad $ad, $output ) {
		if (
			empty( $ad->get_prop( 'global_output' ) ) ||
			empty( $output ) ||
			! Tracking::has_ad_tracking_enabled( $ad )
		) {
			return;
		}

		$blog_id = get_current_blog_id();

		if ( Helpers::is_tracking_method( 'ga' ) ) {
			$can_transmit_pageqs = apply_filters( 'advanced-ads-tracking-query-string', false, $ad->get_id() );
			if ( $can_transmit_pageqs ) {
				if ( ! isset( $this->transmit_pageqs[ $blog_id ] ) ) {
					$this->transmit_pageqs[ $blog_id ] = [];
				}
				$this->transmit_pageqs[ $blog_id ][ $ad->get_id() ] = true;
			}
		}

		if ( ! isset( $this->ad_ids[ $blog_id ] ) ) {
			$this->ad_ids[ $blog_id ] = [];
		}

		Data::collect_blog_data();
		$this->ad_ids[ $blog_id ][] = $ad->get_id();
	}

	/**
	 * Add a wrapper for the ad
	 *
	 * @param array $wrapper The wrapper object.
	 * @param Ad    $ad      The ad object.
	 *
	 * @return array
	 */
	public function add_wrapper( $wrapper, $ad ) {
		// If this ad should not be tracked, don't show wrapper.
		if ( ! Tracking::has_ad_tracking_enabled( $ad, 'min_one' ) ) {
			return $wrapper;
		}

		$frontend_prefix = wp_advads()->get_frontend_prefix();

		// Add the ad id to the wrapper.
		$wrapper[ 'data-' . $frontend_prefix . 'trackid' ]  = $ad->get_id();
		$wrapper[ 'data-' . $frontend_prefix . 'trackbid' ] = get_current_blog_id();
		$wrapper[ 'data-' . $frontend_prefix . 'redirect' ] = (bool) $ad->get_prop( 'tracking.cloaking' ) && ! empty( $ad->get_url() );

		// Add class to wrapper for click tracking, if ad is image or dummy only if it has a URL.
		if (
			Tracking::has_ad_tracking_enabled( $ad, 'click' )
			&& ! ( empty( $ad->get_url() ) && $ad->is_type( [ 'dummy', 'image' ] ) )
		) {
			$wrapper['class'][] = $frontend_prefix . 'target';
		}

		$is_peepso_stream = $ad->get_root_placement() && $ad->get_root_placement()->is_type( 'peepso_stream' );
		if (
			( ! $ad->get_root_placement() || ! Str::contains( 'sticky', $ad->get_root_placement()->get_type() ) || 'timeout' !== $ad->get_prop( 'sticky.trigger' ) ) &&
			empty( $ad->get_prop( 'layer_placement.trigger' ) ) && ! $is_peepso_stream
		) {

			// If not sticky, or sticky but no timeout, AND not layer ad or no trigger, AND not PeepSo abort.
			return $wrapper;
		}

		// Add data attribute if this ad's impressions should be tracked.
		if ( Tracking::has_ad_tracking_enabled( $ad ) ) {
			$wrapper[ 'data-' . $frontend_prefix . 'impression' ] = true;
		}

		// Add delayed marker.
		if ( ! $is_peepso_stream ) {
			$wrapper['data-delayed'] = 1;
		}

		return $wrapper;
	}

	/**
	 * Output ad ids
	 *
	 * @return void
	 */
	public function output_ad_ids(): void {
		if ( Conditional::is_amp() ) {
			return;
		}

		$blog_data = Data::collect_blog_data();
		$variables = [
			'advads_tracking_' => [
				'ads'       => $this->ad_ids,
				'urls'      => $blog_data['ajaxurls'],
				'methods'   => $blog_data['methods'],
				'parallel'  => $blog_data['parallelTracking'],
				'linkbases' => $blog_data['linkbases'],
			],
		];

		// Add Google Analytics specific variables.
		if ( is_multisite() || Helpers::is_tracking_method( 'ga' ) || Helpers::is_forced_analytics() ) {
			$variables['advads_gatracking_'] = [
				'uids'           => $blog_data['gaUIDs'],
				'allads'         => $blog_data['allads'],
				'anonym'         => defined( 'ADVANCED_ADS_DISABLE_ANALYTICS_ANONYMIZE_IP' ) && ADVANCED_ADS_DISABLE_ANALYTICS_ANONYMIZE_IP,
				'transmitpageqs' => $this->transmit_pageqs,
			];
		}

		// transpose variables into string.
		$output = '';
		foreach ( $variables as $dimension => $vars ) {
			foreach ( $vars as $var => $value ) {
				$output .= sprintf( 'var %s = %s;', $dimension . $var, wp_json_encode( $value, ( is_array( $value ) && empty( $value ) ) ? JSON_FORCE_OBJECT : 0 ) );
			}
		}

		printf( '<script id="%stracking">%s</script>', wp_advads()->get_frontend_prefix(), $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
