<?php
/**
 * Frontend Frontend Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Tracking\Constants;
use AdvancedAds\Tracking\Ad_Limiter;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Frontend Tracking.
 */
class Frontend_Tracking implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-can-display-ad', [ $this, 'can_display' ], 10, 2 );
		add_action( 'advanced-ads-rest-ad-request', [ $this, 'track_rest_impression' ] );
		add_filter( 'advanced-ads-privacy-output-attributes', [ $this, 'privacy_output_attributes' ], 10, 2 );

		( new Tracking_Link() )->hooks();

		// Frontend request, not AJAX request.
		if ( ! is_admin() ) {
			// Register two redirect methods, because the first might fail if other plugins also use it.
			add_action( 'plugins_loaded', [ $this, 'url_redirect' ], 1 );
			add_action( 'wp_loaded', [ $this, 'url_redirect' ], 1 );

			// Load functions based on tracking method settings (after the 'parse_query' hook).
			add_filter( 'advanced-ads-pro-passive-cb-for-ad', [ $this, 'add_passive_cb_for_ad' ], 10, 2 );

			// Add click tracking/link cloaking to background placement.
			add_filter( 'advanced-ads-pro-background-url', [ $this, 'filter_background_placement_url' ], 10, 2 );
			add_filter( 'advanced-ads-pro-background-click-matches-script', [ $this, 'add_background_placement_script' ], 10, 2 );
		}
	}

	/**
	 * Pass tracking info to passive cache-busting.
	 *
	 * @param array $data Cache Busting data array.
	 * @param Ad    $ad   Ad instance.
	 *
	 * @return array
	 */
	public function add_passive_cb_for_ad( array $data, Ad $ad ) {
		$data['tracking_enabled'] = Tracking::has_ad_tracking_enabled( $ad );

		return $data;
	}

	/**
	 * Filter the ad link for the background placement.
	 * If link cloaking is active and ad clicks should be tracked, build the tracking URL.
	 *
	 * @param string $url The ad URL.
	 * @param Ad     $ad  Ad instance.
	 *
	 * @return string
	 */
	public function filter_background_placement_url( $url, Ad $ad ) {
		if (
			empty( $url )
			|| ! Tracking::has_ad_tracking_enabled( $ad, 'click' )
			|| ! $ad->get_prop( 'tracking.cloaking' )
		) {
			return $url;
		}

		return Tracking::build_click_tracking_url( $ad );
	}

	/**
	 * Add JS to background placement to enable click tracking.
	 *
	 * @param string $script Other script content, probably empty.
	 * @param Ad     $ad     Ad instance.
	 *
	 * @return string
	 */
	public function add_background_placement_script( $script, Ad $ad ) {
		$frontend_prefix = wp_advads()->get_frontend_prefix();

		ob_start();

		if ( $ad->get_prop( 'tracking.cloaking' ) ) {
			printf( 'e.target.setAttribute( "data-%sredirect", "1");', esc_attr( $frontend_prefix ) );
		}

		if ( Tracking::has_ad_tracking_enabled( $ad, 'click' ) ) {
			printf(
				'e.target.setAttribute( "data-%1$strackid", "%2$d");'
				. 'e.target.setAttribute( "data-%1$strackbid", "%3$d");'
				. 'AdvAdsClickTracker.ajaxSend( e.target );',
				esc_attr( $frontend_prefix ),
				esc_attr( $ad->get_id() ),
				get_current_blog_id()
			);
		}

		return $script . preg_replace( '/\s+/', ' ', ob_get_clean() );
	}

	/**
	 * Check if ad can be displayed based on tracking options
	 *
	 * @since 1.2.6
	 *
	 * @param bool $can_display Whether this ad can be displayed.
	 * @param Ad   $ad          Ad instance.
	 *
	 * @return bool $can_display false if should not be displayed in frontend
	 */
	public function can_display( $can_display, $ad ): bool {
		if ( ! $can_display ) {
			return false;
		}

		return ( new Ad_Limiter( $ad->get_id() ) )->can_display();
	}

	/**
	 * Track impressions for array of ad_ids with the current timestamp.
	 *
	 * @param Ad $ad Ad instance.
	 *
	 * @return void
	 */
	public function track_rest_impression( $ad ): void {
		Tracking::track_impressions( [ $ad->get_id() ], time() );
	}

	/**
	 * If the ad doesn't have impression tracking enabled, add data attribute.
	 *
	 * @param array $attributes Data attributes array.
	 * @param Ad    $ad         Ad instance.
	 *
	 * @return array
	 */
	public function privacy_output_attributes( $attributes, Ad $ad ) {
		if ( Tracking::has_ad_tracking_enabled( $ad ) ) {
			return $attributes;
		}

		$attributes['no-track'] = 'impressions';

		return $attributes;
	}

	/**
	 * Redirect the visitor if he uses click tracking
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function url_redirect(): void {
		$start_time  = microtime( true );
		$request_uri = trim( rawurldecode( Params::server( 'REQUEST_URI' ) ), '/' );
		$host        = Params::server( 'HTTP_HOST' );
		if ( $host && $sub_pos = strpos( home_url(), $host ) ) { // phpcs:ignore
			$subdirectory = trim( substr( home_url(), $sub_pos + mb_strlen( $host ) ), '/' );
			if ( $subdirectory ) {
				$request_uri = str_replace( $subdirectory . '/', '', $request_uri );
			}
		}

		$linkbase  = wp_advads_tracking()->options->get( 'linkbase', Constants::DEFAULT_CLICK_LINKBASE );
		$permalink = get_option( 'permalink_structure' );

		// Abort if this is obviously not a tracking link.
		if ( $permalink ) {
			if ( strpos( $request_uri, $linkbase ) !== 0 ) {
				return;
			}
		} elseif ( ! Params::get( $linkbase ) ) {
			return;
		}

		$ad_id = false;

		// Check if the current url has a number in it.
		if ( $permalink ) {
			$matches = [];
			preg_match( '@/(\d+)\??@', $request_uri, $matches );

			if ( isset( $matches[1] ) ) {
				$ad_id = (int) trim( $matches[1], '/' );
			}
		} else {
			$ad_id = Params::get( $linkbase, 0, FILTER_VALIDATE_INT );
		}

		if ( empty( $ad_id ) ) {
			return;
		}

		// Redirect, if ad id was found.
		$ad = wp_advads_get_ad( $ad_id );
		if ( ! $ad ) {
			return;
		}

		$url = $ad->get_url();

		if ( ( strpos( $request_uri, '?advads_amp' ) !== false ) && $ad->is_type( 'plain' ) ) {
			// Extract url from content if plain ad on amp.
			$matches = Helpers::get_url_from_string( $ad->get_content() );
			if ( ! empty( $matches[0] ) ) {
				$url = $matches[0];
			}
		}

		if ( empty( $url ) ) {
			return;
		}
		// Need a referrer because the click base url does not contain any information on the post where the ad was displayed and clicked.
		$referrer     = Params::server( 'HTTP_REFERER' );
		$placeholders = [ '[POST_ID]', '[POST_SLUG]', '[CAT_SLUG]' ];
		$placeholders = array_merge( $placeholders, array_map( 'rawurlencode', $placeholders ) );

		if ( $referrer && is_string( $referrer ) ) {

			/**
			 *  If called within the 'plugins_loaded' action, prevent redirecting
			 *  url_to_postid need to be called after the 'init' hook. Also stop tracking
			 *
			 *  [https://codex.wordpress.org/Function_Reference/url_to_postid]
			 */
			if ( ! did_action( 'init' ) ) {
				return;
			}

			// Hotfix for WPML – remove url_to_postid filter to get an unchanged url.
			global $sitepress;
			remove_filter( 'url_to_postid', [ $sitepress, 'url_to_postid' ] );

			$post_id = url_to_postid( $referrer );

			add_filter( 'url_to_postid', [ $sitepress, 'url_to_postid' ] );

			$post = get_post( $post_id );

			parse_str( Params::server( 'QUERY_STRING' ), $tracking_query_args );

			if ( $post ) {
				// The post ID was found by its URL.
				$cats = get_the_category( $post->ID );

				$cats_slugs = [];
				foreach ( $cats as $cat ) {
					$cats_slugs[] = $cat->slug;
				}

				// $placeholders exist as escaped and unescaped elements.
				$replacements = [ $post->ID, $post->post_name, implode( ',', $cats_slugs ) ];
				$replacements = array_merge( $replacements, $replacements );
				$url          = str_replace( $placeholders, $replacements, $url );
			} else {
				/***
				 *  Post ID not found by its url ( eg: landing page )
				 */
				$expl_url = explode( '?', $url );
				if ( 1 < count( $expl_url ) ) {
					$baseurl = $expl_url[0];
					parse_str( $expl_url[1], $parsed );

					$query_arr = [];
					foreach ( $parsed as $key => $value ) {
						if ( ! in_array( $value, $placeholders, true ) ) {
							$query_arr[ $key ] = $value;
						}
					}
					$url = add_query_arg( $query_arr, $baseurl );
				}
			}

			/**
			 * Pass query arguments from tracking link to the target url.
			 */
			if ( ! empty( $tracking_query_args ) ) {
				// Do not include the tracking link base.
				if ( isset( $tracking_query_args[ $linkbase ] ) ) {
					unset( $tracking_query_args[ $linkbase ] );
				}
				$url = add_query_arg( $tracking_query_args, $url );
			}

			/**
			 * Pass query string from referer (if any);
			 */
			$can_transmit_qs = apply_filters( 'advanced-ads-tracking-query-string', false, $ad_id );
			if ( $can_transmit_qs ) {
				$parsed_query = wp_parse_url( $referrer, PHP_URL_QUERY );
				if ( ! empty( $parsed_query ) ) {
					parse_str( $parsed_query, $referer_query );
					if ( ! empty( $referer_query ) ) {
						$url = add_query_arg( $referer_query, $url );
					}
				}
			}
		} else {
			$url = str_replace( $placeholders, '', $url );
		}

		// Replace [AD_ID] with the ad’s ID, if given.
		$url = str_replace( [ '[AD_ID]', '%5BAD_ID%5D' ], $ad_id, $url );

		if ( ! Helpers::is_tracking_method( 'ga' ) && Tracking::has_ad_tracking_enabled( $ad, 'click' ) ) {
			Tracking::track_click( $ad->get_id(), $start_time );
		}

		/**
		 * Last chance for other scripts to change the redirect URL
		 * originally introduced to allow "fixing" issues when a wrong URL was created
		 */
		$url      = apply_filters( 'advanced-ads-tracking-redirect-url', $url );
		$nofollow = wp_advads_tracking()->options->get( 'nofollow' );

		if ( $nofollow ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
		} else {
			header( 'X-Robots-Tag: noindex' );
		}

		// redirect to target URL.
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'HTTP/1.1 307  Temporary Redirect' );
		header( 'Location: ' . esc_url_raw( $url ) );

		die();
	}
}
