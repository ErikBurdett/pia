<?php
/**
 * Class add tracking link.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use DOMDocument;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Tracking\Helpers;
use AdvancedAds\Utilities\Conditional;
use AdvancedAds\Tracking\Utilities\Tracking;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Tracking Link.
 */
class Tracking_Link implements Integration_Interface {

	/**
	 * Correspondence between ad ID-s and target link if any, for Google Analytics usage
	 *
	 * @var array
	 */
	private $ad_targets = [];

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-rest-ad-content', [ $this, 'add_tracking_link' ], 10, 2 );
		add_filter( 'advanced-ads-output-inside-wrapper', [ $this, 'add_tracking_link' ], 10, 2 );
		add_filter( 'advanced-ads-get-ad-targets', [ $this, 'get_ad_targets' ] );
	}

	/**
	 * Get ad targets.
	 *
	 * @return array
	 */
	public function get_ad_targets(): array {
		return $this->ad_targets;
	}

	/**
	 * Add a link to the ad content either for the %link% placeholder or a wrapper.
	 *
	 * @since 1.1.0
	 *
	 * @param string $content The ad content.
	 * @param Ad     $ad      Ad instance.
	 *
	 * @return string
	 */
	public function add_tracking_link( $content, Ad $ad ): string {
		// Do not add link if click tracking is not supported by the ad type.
		if ( ! Helpers::is_clickable_type( $ad->get_type() ) ) {
			return $content;
		}

		$options   = wp_advads_tracking()->options->get_all();
		$amp_plain = false;
		$url       = \AdvancedAds\Tracking\Helpers::get_ad_link( $ad );
		$is_amp    = Conditional::is_amp();

		// Extract custom link if plain ad on amp.
		if ( $is_amp && $ad->is_type( 'plain' ) && ! $url ) {
			$amp_plain = true;
			$matches   = Helpers::get_url_from_string( $content );
			if ( ! empty( $matches[0] ) ) {
				$url     = $matches[0];
				$link    = $url;
				$content = str_replace( sprintf( 'href="%s"', $link ), 'href="%link%"', $content );
			}
		}

		// We haven't found a URL, so we return the original content.
		if ( ! $url ) {
			return $content;
		}

		$link = Tracking::build_click_tracking_url( $ad );
		if ( ! $link ) {
			return $content;
		}

		$bid                                       = get_current_blog_id();
		$this->ad_targets[ $bid ][ $ad->get_id() ] = $url;

		$attributes = [
			'data-bid'        => $bid,
			'data-no-instant' => true,
			'href'            => $link,
			'rel'             => [ 'noopener' ],
			'class'           => [],
			'target'          => Helpers::get_ad_target( $ad, true ),
		];

		// Parse rel attribute.
		foreach ( [ 'nofollow', 'sponsored' ] as $relationship ) {
			$option = $ad->get_prop( 'tracking.' . $relationship ) ?? 'default';
			if ( 'default' === $option ) {
				$option = ! empty( $options[ $relationship ] );
			}

			if ( $option ) {
				$attributes['rel'][] = $relationship;
			}
		}

		if ( strpos( $content, '%link%' ) !== false ) {
			// Add custom parameter to recognise amp origin.
			if ( $amp_plain ) {
				$attributes['href'] = add_query_arg( [ 'advads_amp' => '' ], $attributes['href'] );
			}

			// Return content if there aren't any link tags.
			if ( ! preg_match_all( '/(<a[^<]+?%link%[^<]+?>)/', $content, $links_to_replace ) ) {
				return $content;
			}

			if ( ! Tracking::has_ad_tracking_enabled( $ad, 'click' ) ) {
				return str_replace( '%link%', esc_url( $url ), $content );
			}

			// Add `notrack` class to disable js-based tracking for this link in case it is not a redirect link on Google Analytics.
			if ( ! Helpers::is_tracking_method( 'ga' ) && ! Helpers::is_forced_analytics() && ( Helpers::is_tracking_method( 'frontend' ) && $ad->get_prop( 'tracking.cloaking' ) ) ) {
				$attributes['class'][] = 'notrack';
			}

			if ( ! $ad->get_prop( 'tracking.cloaking' ) && ! $is_amp ) {
				$attributes['href'] = esc_url( $url );
			}

			$attributes = array_filter( $attributes );
			$attributes = $this->filter_link_attributes( $attributes, $ad );

			foreach ( $links_to_replace[0] as $link_tag ) {
				$link_attributes = $this->attributes_merge_recursive( $this->parse_link_attributes( $link_tag ), $attributes );
				$content         = str_replace( $link_tag, sprintf( '<a %s>', $this->create_attributes_string( $link_attributes ) ), $content );
			}

			return $content;
		}

		// There is no placeholder. If the content of a plain ad itself contains links abort and return the content.
		if ( $ad->is_type( 'plain' ) && preg_match( '/<a[\s]+/', $content ) ) {
			return $content;
		}

		// Wrap ad into tracking link if delivered by ad server or amp and url field is empty.
		if ( $is_amp || $ad->get_prop( 'tracking.cloaking' ) || ( $ad->is_parent_placement() && $ad->get_root_placement()->is_type( 'server' ) && Tracking::has_ad_tracking_enabled( $ad, 'click' ) ) ) {
			$attributes['class'][] = 'notrack';
		} else {
			$placeholders = [ '[POST_ID]', '[POST_SLUG]', '[CAT_SLUG]', '[AD_ID]' ];
			$placeholders = array_merge( $placeholders, array_map( 'rawurlencode', $placeholders ) );
			// Use str_replace to decide whether URL has placeholder.
			str_replace( $placeholders, '', $url, $count );

			// If there are placeholders, use the redirect click-tracking link.
			if ( $count ) {
				$attributes['class'][] = 'notrack';
			} else {
				// Else use the original, uncloaked URL.
				$attributes['class'][] = 'a2t-link';
				$attributes['href']    = esc_url( $url );
				unset( $attributes['data-bid'] );
			}
		}

		if ( $ad->is_type( 'image' ) ) {
			$id                         = $ad->get_image_id() ?? '';
			$alt                        = trim( esc_textarea( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) );
			$aria_label                 = ! empty( $alt ) ? $alt : wp_basename( get_the_title( $id ) );
			$attributes['aria-label'][] = $aria_label;
		}

		if ( $ad->is_type( 'dummy' ) ) {
			$attributes['aria-label'][] = 'dummy';
		}

		$attributes = $this->filter_link_attributes( $attributes, $ad );

		return sprintf( '<a %s>%s</a>', $this->create_attributes_string( array_filter( $attributes ) ), $content );
	}

	/**
	 * Filter the tracking links attributes.
	 * They are used in multiple places, therefore wrap them in a function.
	 * Ensure the href is present so the `a` tag is valid.
	 *
	 * @param array $attributes Generated HTML attributes.
	 * @param Ad    $ad         Ad instance.
	 *
	 * @return array
	 */
	private function filter_link_attributes( array $attributes, Ad $ad ) {
		/**
		 * Allow to filter the link attributes.
		 *
		 * @var array $attributes The generated attributes. Attribute name is the key and the value as the value. If multiple values exist for a key, they're stored in an array of strings.
		 * @var Ad    $ad         Ad instance.
		 */
		$attributes = (array) apply_filters( 'advanced-ads-tracking-link-attributes', $attributes, $ad );
		if ( ! array_key_exists( 'href', $attributes ) ) {
			$attributes['href'] = '';
		}

		return $attributes;
	}

	/**
	 * Create a string from array of attributes to be used on HTML element.
	 *
	 * @param array $attributes Array of attributes, attribute name as key, if value is array it gets imploded into space-separated string.
	 *
	 * @return string
	 */
	private function create_attributes_string( $attributes ) {
		return implode(
			' ',
			array_map(
				function ( $value, $name ) {
					if ( is_array( $value ) ) {
						$value = implode( ' ', array_unique( $value ) );
					}
					$sep = strpos( $value, '"' ) !== false ? "'" : '"';
					return sprintf( '%1$s=%2$s%3$s%2$s', $name, $sep, $value );
				},
				$attributes,
				array_keys( $attributes )
			)
		);
	}

	/**
	 * Merge two arrays with attributes recursively.
	 * If the values is a string, the value form array2 replaces array1.
	 * If the value is an array, the array from array2 gets merged into array1.
	 *
	 * @param array $array1 This array holds the original values that may get overridden.
	 * @param array $array2 This array holds the values that get merged into $array1.
	 *
	 * @return array
	 */
	private function attributes_merge_recursive( array $array1, array $array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => $value ) {
			if ( isset( $merged[ $key ] ) && is_array( $value ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = array_merge( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Get all defined attributes from link tag.
	 *
	 * @param string $input HTML string link tag.
	 *
	 * @return array
	 */
	private function parse_link_attributes( $input ) {
		if ( ! extension_loaded( 'dom' ) ) {
			return [];
		}
		$libxml_previous_state = libxml_use_internal_errors( true );
		$dom                   = new DOMDocument( '1.0', 'utf-8' );
		$dom->loadHTML( '<!DOCTYPE html><html><body>' . htmlentities( $input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false ) . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $libxml_previous_state );

		$attributes = [];
		foreach ( $dom->getElementsByTagName( 'a' ) as $link ) {
			foreach ( $link->attributes as $attribute ) {
				$attributes[ $attribute->name ] = in_array( $attribute->name, [ 'class', 'rel' ], true ) ? explode( ' ', $attribute->value ) : $attribute->value;
			}
		}

		return $attributes;
	}
}
