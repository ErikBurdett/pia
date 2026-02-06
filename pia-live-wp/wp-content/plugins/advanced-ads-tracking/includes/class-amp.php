<?php
/**
 * AMP.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * AMP.
 */
class AMP implements Integration_Interface {

	/**
	 * Holds ads by blog_id.
	 *
	 * @var array
	 */
	private $ads = [];

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp', [ $this, 'register_actions' ] );
	}

	/**
	 * Collect ads and register actions for tracking methods on ad output.
	 *
	 * @return void
	 */
	public function register_actions(): void {
		// Check if is amp.
		if ( ! Conditional::is_amp() ) {
			return;
		}

		add_action( 'advanced-ads-ad-output-ready', [ $this, 'get_tracking_methods' ] );
	}

	/**
	 * Collect ads and add relevant hooks for JS (amp-pixel) and GA (amp-analytics) tracking.
	 *
	 * @param Ad $ad Ad instance.
	 *
	 * @return void
	 */
	public function get_tracking_methods( Ad $ad ): void {
		// If impression tracking is not allowed for this ad, skip it.
		if ( ! Tracking::has_ad_tracking_enabled( $ad ) ) {
			return;
		}

		// Try setting the tracking method to amp-pixel.
		add_filter( 'advanced-ads-tracking-method', [ $this, 'set_tracking_method' ] );

		$blog_id = get_current_blog_id();

		// Collect ad ids per blog.
		$this->ads[ $blog_id ][ $ad->get_id() ] = $ad->get_title();

		// Add tracking pixel if method is amp-pixel.
		if ( 'amp_pixel' === $this->get_tracking_method( $blog_id ) ) {
			$this->tracking_pixel_actions();
		}

		// Add google analytics amp code.
		if (
			( 'ga' === $this->get_tracking_method( $blog_id ) || Helpers::is_forced_analytics() )
			&& ! empty( $this->get_gauid( $blog_id ) )
		) {
			$this->amp_analytics_actions();
		}
	}

	/**
	 * If a custom AJAX handler is used, set it to amp tracking.
	 * If frontend tracking method is used with admin-ajax.php set it to `onrequest`.
	 *
	 * @param string $method tracking method as set under Advanced Ads > Settings > Tracking.
	 *
	 * @return string
	 */
	public function set_tracking_method( $method ): string {
		return 'frontend' !== $method ? $method : 'amp_pixel';
	}

	/**
	 * Get the tracking method for the current blog.
	 *
	 * @param int $blog_id Current blog id.
	 *
	 * @return string
	 */
	private function get_tracking_method( $blog_id ): string {
		$options = $this->get_blog_option( $blog_id );

		return Helpers::get_tracking_method( isset( $options['method'] ) ? $options['method'] : '' );
	}

	/**
	 * Output tracking pixel for local AMP tracking.
	 *
	 * @return void
	 */
	private function tracking_pixel_actions(): void {
		// Transitional/Standard mode.
		add_action( 'wp_footer', [ $this, 'add_tracking_pixel' ] );

		// WP AMP — Accelerated Mobile Pages for WordPress and WooCommerce (https://codecanyon.net/item/wp-amp-accelerated-mobile-pages-for-wordpress-and-woocommerce/16278608).
		add_action( 'amphtml_after_footer', [ $this, 'add_tracking_pixel' ] );

		// AMP - AMP Project Contributors (https://wordpress.org/plugins/amp/), Reader mode.
		// AMP for WP - Accelerated Mobile Pages for WordPress (https://wordpress.org/plugins/accelerated-mobile-pages/).
		add_action( 'amp_post_template_footer', [ $this, 'add_tracking_pixel' ] );

		// AMP WP - pixelative (https://wordpress.org/plugins/amp-wp/).
		add_action( 'amp_wp_template_footer', [ $this, 'add_tracking_pixel' ] );
	}

	/**
	 * Output the <amp-pixel>.
	 *
	 * @return void
	 */
	public function add_tracking_pixel(): void {
		$referrer = preg_replace(
			'%^/?(.+?)[/?&]*amp(?:=1|/)?$%',
			'$1',
			! is_null( $GLOBALS['wp']->request ) ? $GLOBALS['wp']->request : Params::server( 'REQUEST_URI' )
		);

		if ( substr( $referrer, 0, 1 ) !== '/' ) {
			$referrer = '/' . $referrer;
		}

		// One pixel for each blog id.
		foreach ( $this->ads as $bid => $ads ) {
			printf(
				'<amp-pixel src="%s" layout="nodisplay"></amp-pixel>',
				esc_url(
					add_query_arg(
						[
							'ads'      => array_keys( $ads ),
							'action'   => Constants::TABLE_IMPRESSIONS,
							'referrer' => rawurlencode( $referrer ),
							'bid'      => $bid,
							'handler'  => rawurlencode( 'Frontend on AMP' ),
						],
						Helpers::is_legacy_ajax() ? admin_url( 'admin-ajax.php' ) : content_url( '/ajax-handler.php' )
					)
				)
			);
		}
	}

	/**
	 * Get the GA tracking ID.
	 *
	 * @param int $blog_id Current blog id.
	 *
	 * @return string tracking id or empty string.
	 */
	private function get_gauid( $blog_id ): string {
		$options = $this->get_blog_option( $blog_id );
		if ( empty( $options['ga-UID'] ) ) {
			return '';
		}

		return $options['ga-UID'];
	}

	/**
	 * Output relevant scripts for AMP Analytics tracking.
	 *
	 * @return void
	 */
	private function amp_analytics_actions(): void {
		$actions = [
			// AMP - AMP Project Contributors (https://wordpress.org/plugins/amp/), Transitional/Standard mode.
			'amp'        => 'wp',
			// AMP - AMP Project Contributors (https://wordpress.org/plugins/amp/), Reader mode.
			// AMP for WP - Accelerated Mobile Pages for WordPress (https://wordpress.org/plugins/accelerated-mobile-pages/).
			'amp_reader' => 'amp_post_template',
			// AMP WP - pixelative (https://wordpress.org/plugins/amp-wp/).
			'amp_wp'     => 'amp_wp_template',
		];

		foreach ( $actions as $action ) {
			$hook = did_action( $action . '_head' ) ? $action . '_footer' : $action . '_head';
			add_action( $hook, [ $this, 'add_amp_analytics' ] );
			add_action( $action . '_footer', [ $this, 'add_amp_analytics_ads' ] );
		}
	}

	/**
	 * Output the amp-analytics JS; only once per request.
	 *
	 * @return void
	 */
	public function add_amp_analytics(): void {
		static $done = false;
		if ( $done ) {
			return;
		}

		$done = true;
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		echo '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>';
	}

	/**
	 * Output the <amp-analytics> script once per blog_id with all ads included.
	 *
	 * @return void
	 */
	public function add_amp_analytics_ads(): void {
		foreach ( $this->ads as $blog_id => $ads ) {
			$ga_uid = $this->get_gauid( $blog_id );
			if ( empty( $ga_uid ) ) {
				continue;
			}

			$amp_analytics = [
				'requests' => [
					'impressionEvent' => '${event}&ni=1',
				],
				'vars'     => [
					'account'       => $ga_uid,
					'eventCategory' => 'Advanced Ads',
					'eventAction'   => __( 'Impressions', 'advanced-ads-tracking' ),
				],
				'triggers' => [],
			];

			foreach ( $ads as $ad_id => $ad_title ) {
				$amp_analytics['triggers'][ 'impression of ad ' . $ad_id ] = [
					'on'      => 'visible',
					'request' => 'impressionEvent',
					'vars'    => [
						'eventLabel' => sprintf( '[%d] %s', $ad_id, $ad_title ),
					],
				];
			}

			printf( '<amp-analytics type="googleanalytics"><script type="application/json">%s</script></amp-analytics>', wp_json_encode( $amp_analytics ) );
		}
	}

	/**
	 * Get options for the blog specified.
	 * Defaults to blog id 1.
	 *
	 * @param int $blog_id Current blog id.
	 *
	 * @return array
	 */
	private function get_blog_option( $blog_id ) {
		static $options;

		if ( ! empty( $options[ $blog_id ] ) ) {
			return $options[ $blog_id ];
		}

		$option = function_exists( 'get_blog_option' )
			? get_blog_option( $blog_id, Constants::OPTIONS_SLUG, [] )
			: get_option( Constants::OPTIONS_SLUG, [] );

		$options[ $blog_id ] = $option;

		return $option;
	}
}
