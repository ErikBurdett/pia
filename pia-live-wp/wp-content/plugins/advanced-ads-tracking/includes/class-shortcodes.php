<?php
/**
 * The class hold shortcodes for tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking;

use AdvancedAds\Constants;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcodes
 */
class Shortcodes implements Integration_Interface {
	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_shortcode( 'the_ad_impressions', [ $this, 'ad_impressions' ] );
		add_shortcode( 'the_ad_clicks', [ $this, 'ad_clicks' ] );
	}

	/**
	 * Display impressions of an ad in the frontend
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string
	 */
	public function ad_impressions( $atts ): string {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'the_ad_impressions' );
		$id   = absint( $atts['id'] );
		if ( ! $id ) {
			return '';
		}

		if ( $id < 1 || Constants::POST_TYPE_AD !== get_post_type( $id ) ) {
			return '';
		}

		return Database::get_ad_total_impressions( $id );
	}

	/**
	 * Display clicks of an ad in the frontend
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string
	 */
	public function ad_clicks( array $atts ): string {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'the_ad_clicks' );
		$id   = absint( $atts['id'] );
		if ( ! $id ) {
			return '';
		}

		if ( $id < 1 || Constants::POST_TYPE_AD !== get_post_type( $id ) ) {
			return '';
		}

		return Database::get_ad_total_clicks( $id );
	}
}
