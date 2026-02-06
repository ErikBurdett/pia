<?php
/**
 * Frontend helper for the AdSense fallback
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

namespace AdvancedAds\Pro;

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Group;
use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Options;

/**
 * Frontend class
 */
class Adsense implements Integration_Interface {
	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		$cache_busting = Options::instance()->get( 'pro.cache-busting' );

		// No CB, abort.
		if ( empty( $cache_busting['enabled'] ) ) {
			return;
		}

		add_filter( 'advanced-ads-cache-busting-item', [ $this, 'filter_cache_busting_item' ], 10, 2 );
		add_filter( 'advanced-ads-pro-ad-needs-backend-request', [ $this, 'filter_cache_busting_method' ], 10, 2 );
		add_filter( 'advanced-ads-output-wrapper-options', [ $this, 'filter_ad_wrapper_options' ], 10, 2 );
		add_action( 'wp_loaded', [ $this, 'inline_script' ] );
	}

	/**
	 * Add inline JS variables
	 *
	 * @return void
	 */
	public function inline_script() {
		wp_advads()->json->add(
			[
				'adHealthNotice' =>
					[
						'enabled' => \Advanced_Ads_Ad_Health_Notices::notices_enabled(),
						'pattern' => sprintf(
							/* translators: ad title. */
							__( 'AdSense fallback was loaded for empty AdSense ad "%s"', 'advanced-ads-pro' ),
							'[ad_title]'
						),
					],
			]
		);
	}

	/**
	 * Add CSS classes with data about AdSense fallback
	 *
	 * @param array $options wrapper options.
	 * @param Ad    $ad      the current ad being rendered.
	 *
	 * @return mixed
	 */
	public function filter_ad_wrapper_options( $options, $ad ) {
		// Not AdSense, abort.
		if ( ! $ad->is_type( 'adsense' ) ) {
			return $options;
		}

		// No CB, abort.
		$placement = $ad->get_root_placement();
		if ( ! $placement || 'off' === $placement->get_prop( 'cache-busting' ) ) {
			return $options;
		}

		$fallback = $this->get_practical_fallback( $ad );

		// No fallback, abort.
		if ( 'none' === $fallback ) {
			return $options;
		}

		$options['class'] = [ 'gas_fallback-ad_' . $ad->get_id() . '-' . $fallback ];

		return $options;
	}

	/**
	 * Add cache busting item data about fallbacks if needed
	 *
	 * @param array $item         current item data.
	 * @param array $ad_arguments ad arguments and method.
	 *
	 * @return mixed
	 */
	public function filter_cache_busting_item( $item, $ad_arguments ) {
		if ( empty( $ad_arguments['args']['item'] ) ) {
			return $item;
		}

		$exploded_item = explode( '_', $ad_arguments['args']['item'] );

		$placement_item = [
			'type' => $exploded_item[0],
			'id'   => (int) $exploded_item[1],
		];

		// Works only on a placement with an AdSense ad for now (not on an AdSense ad within a group).
		if ( 'ad' !== $placement_item['type'] ) {
			return $item;
		}

		$ad = wp_advads_get_ad( $placement_item['id'] );

		if ( ! $ad || ! $ad->is_type( 'adsense' ) ) {
			return $item;
		}

		$fallback = $this->get_practical_fallback( $ad );

		if ( ! $fallback || 'none' === $fallback ) {
			return $item;
		}

		if ( empty( $placement_item['adsense_fallback'] ) || ! is_array( $placement_item['adsense_fallback'] ) ) {
			$item['adsense_fallback'] = [];
		}

		$item['adsense_fallback'][ 'ad_' . $placement_item['id'] ] = $fallback;

		$exploded        = explode( '_', $fallback );
		$fallback_object = 'ad' === $exploded[0]
			? wp_advads_get_ad( (int) $exploded[1] )
			: wp_advads_get_group( (int) $exploded[1] );

		$item['ads'] += $this->collect_passive_ad_info( $fallback_object );

		if ( is_a_group( $fallback_object ) ) {
			$item['adsense_fallback_group_info'] = [
				'id'             => $fallback_object->get_id(),
				'name'           => $fallback_object->get_title(),
				'weights'        => $fallback_object->get_ad_weights(),
				'type'           => $fallback_object->get_type(),
				'ordered_ad_ids' => $fallback_object->get_ordered_ad_ids(),
				'ad_count'       => $fallback_object->get_ad_count(),
			];
		}

		return $item;
	}

	/**
	 * Collect passive cache busting info of all fallback ads.
	 *
	 * @param Ad|Group $fallback fallback obejct.
	 *
	 * @return array[]
	 */
	private function collect_passive_ad_info( $fallback ) {
		$ads = [];

		if ( is_an_ad( $fallback ) ) {
			return [ $fallback->get_id() => \Advanced_Ads_Pro_Module_Cache_Busting::get_instance()->get_passive_cb_for_ad( $fallback ) ];
		}

		if ( is_a_group( $fallback ) ) {
			foreach ( $fallback->get_ads() as $id => $ad ) {
				$ads[ $id ] = \Advanced_Ads_Pro_Module_Cache_Busting::get_instance()->get_passive_cb_for_ad( $ad );
			}
		}

		return $ads;
	}

	/**
	 * Force CB if the ad normally doesn't need CB
	 *
	 * @param string $method CB method.
	 * @param Ad     $ad the ad.
	 *
	 * @return string
	 */
	public function filter_cache_busting_method( $method, $ad ) {
		// "static" = NO CB. If it's not "static" no need to intervene or not AdSense, abort.
		if ( 'static' !== $method || ! $ad->is_type( 'adsense' ) ) {
			return $method;
		}

		// If still "static" and we have a fallback, force CB.
		return $this->get_practical_fallback( $ad ) ? 'passive' : $method;
	}

	/**
	 * Get the actual fallback item for an AdSense ad (ad level if any otherwise return the site wide fallback)
	 *
	 * @param Ad $ad the current ad.
	 *
	 * @return false|string
	 */
	public function get_practical_fallback( $ad ) {
		$ad_level_fallback = self::get_fallback( $ad );

		if ( 'none' === $ad_level_fallback ) {
			return false;
		}

		$global_fallback = self::get_global_fallback();

		if ( 'default' === $ad_level_fallback ) {
			return 'none' === $global_fallback ? false : $global_fallback;
		}

		return $ad_level_fallback;
	}

	/**
	 * Get the fallback CB item for a given AdSense ad
	 *
	 * @param Ad $ad the adsense ad.
	 *
	 * @return string
	 */
	public static function get_fallback( $ad ) {
		$fallback = $ad->get_prop( 'adsense_fallback' );

		return $fallback ?? 'default';
	}

	/**
	 * Get the site wide fallback CB item object
	 *
	 * @return Ad|Group|bool `false` if no global fallback found.
	 */
	public static function get_global_fallback_object() {
		$item = self::get_global_fallback();
		if ( ! $item || 'none' === $item ) {
			return false;
		}

		$exploded_item = explode( '_', $item );

		return 'ad' === $exploded_item[0]
			? wp_advads_get_ad( (int) $exploded_item[1] )
			: wp_advads_get_group( (int) $exploded_item[1] );
	}

	/**
	 * Get the global fallback item
	 *
	 * @return string
	 */
	public static function get_global_fallback() {
		$fallback = Options::instance()->get( 'adsense.adsense_fallback' );

		return $fallback ?? 'none';
	}
}
