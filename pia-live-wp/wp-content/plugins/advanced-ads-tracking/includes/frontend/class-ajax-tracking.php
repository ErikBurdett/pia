<?php
/**
 * Frontend Ajax Tracking.
 *
 * @package AdvancedAds\Tracking
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.6.0
 */

namespace AdvancedAds\Tracking\Frontend;

use Advanced_Ads_Privacy;
use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Ads\Types\Dummy;
use AdvancedAds\Constants;
use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Framework\Utilities\Arr;
use AdvancedAds\Framework\Utilities\Params;
use AdvancedAds\Tracking\Utilities\Tracking;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend Ajax Tracking.
 */
class Ajax_Tracking implements Integration_Interface {

	/**
	 * Store info about ads loaded using ajax
	 *
	 * @var array
	 */
	private $ajax_ads = [];

	/**
	 * Holds placements for ads that have been loaded through AJAX.
	 *
	 * @var array
	 */
	private $cache_busting_placements = [];

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'shutdown', [ $this, 'track_on_shutdown' ] );
		add_filter( 'advanced-ads-cache-busting-item', [ $this, 'collect_cache_busting_placements' ], 10, 2 );
		add_action( 'advanced-ads-ad-output-ready', [ $this, 'record_ajax_ad' ], 10, 2 );
	}

	/**
	 * Get placements for cache busting items and collect them to track later on.
	 *
	 * @param array $result Cache busting item results array.
	 * @param array $args   Args for cache busting item.
	 *
	 * @return array The unmodified cache busting item.
	 */
	public function collect_cache_busting_placements( $result, $args ): array {
		if ( 'placement' !== $result['method'] || $this->is_delayed_placement( $args['args'] ) ) {
			return $result;
		}

		$this->cache_busting_placements[] = (int) $result['id'];

		return $result;
	}

	/**
	 * Track impression on PHP shutdown.
	 * Used for ads loaded via AJAX, i.e. AJAX-cache busting, Ad Server.
	 *
	 * @return void
	 */
	public function track_on_shutdown(): void {
		$ajax_ads = $this->sanitize_ads( $this->ajax_ads );

		// If we don't have any ads, return early.
		if ( empty( $ajax_ads ) ) {
			return;
		}

		$start_time = microtime( true );

		// If we don't need to check for placements, track the found ads and return.
		// We only need to check placements for ad selects form main plugin or Pro Ad Server module.
		$action = Params::request( 'action' );
		if ( ! in_array( $action, [ 'advads_ad_select', 'aa-server-select' ], true ) ) {
			Tracking::track_impressions( $ajax_ads, $start_time );
			return;
		}

		$placements = wp_advads_get_placements();

		if ( 'advads_ad_select' === $action ) {
			// If this is an AJAX cb request, but the placement is not, remove it.
			$placements = array_filter(
				$placements,
				function ( $placement_id ) {
					return in_array( $placement_id, $this->cache_busting_placements, true );
				},
				ARRAY_FILTER_USE_KEY
			);
		} elseif ( 'aa-server-select' === $action ) {
			// If this is an Ad Server request, but the placement is not, remove it.
			$placements = array_filter(
				$placements,
				function ( $placement ) {
					return $placement->is_type( 'server' );
				}
			);
		}

		// Ad groups cache.
		$ad_groups = [];

		$privacy = Advanced_Ads_Privacy::get_instance();

		foreach ( $placements as $placement_id => $placement ) {
			$grouped_ads    = [];
			$ad_group_count = 1;

			// If this is a group, get the group and see how many ads should be shown.
			if ( 'group' === $placement->get_item_type() ) {
				$group_id    = $placement->get_item_object()->get_id();
				$grouped_ads = $this->get_ads_in_group( $group_id );
				// We don't have an instance for this ad group yet.
				if ( ! array_key_exists( $placement->get_item(), $ad_groups ) ) {
					$ad_groups[ $placement->get_item() ] = wp_advads_get_group( $group_id );
				}
				$ad_group_count = $ad_groups[ $placement->get_item() ]->get_ads_count();
			}

			foreach ( $ajax_ads as $ajax_ad ) {
				if (
					// not the correct ad for the current placement.
					$ajax_ad['placement_id'] !== $placement_id
					// see if part of ad group.
					|| ( ! empty( $grouped_ads ) && ! in_array( (int) $ajax_ad['id'], $grouped_ads, true ) )
				) {
					continue;
				}

				$the_ad = wp_advads_get_ad( (int) $ajax_ad['id'] );

				if (
					Arr::get( $privacy->options(), 'enabled' )
					&& $privacy->ad_type_needs_consent( $the_ad->get_type() )
					&& ! in_array( Params::post( 'consent' ), [ 'accepted', 'not_needed' ], true )
				) {
					continue;
				}

				// We have found an ad for this placement, track it.
				Tracking::track_impression( (int) $ajax_ad['id'], $start_time );
				// if we have found enough ads for this placement, check the next one.
				if ( 0 === --$ad_group_count ) {
					break;
				}
			}
		}
	}

	/**
	 * Record ajax ads information for impression tracking purpose
	 *
	 * @param Ad     $ad     Ad instance.
	 * @param string $output The ad output string.
	 *
	 * @return void
	 */
	public function record_ajax_ad( Ad $ad, $output ): void {
		$global_output = $ad->get_prop( 'global_output' ) ?? false;

		if ( ! $global_output ) {
			return;
		}

		$ad_data = [
			'type'             => 'ad',
			'id'               => $ad->get_id(),
			'title'            => $ad->get_title(),
			'output'           => $output,
			'tracking_enabled' => Tracking::has_ad_tracking_enabled( $ad ),
		];

		$placement = $ad->get_root_placement();

		if ( $placement ) {
			$ad_data['placement_id'] = $placement->get_id();
		}

		$this->ajax_ads[] = $ad_data;
	}

	/**
	 * Sanitize and filter ads array.
	 *
	 * @param array $ads Array of ads to be sanitized and filtered.
	 *
	 * @return array Sanitized and filtered array of ads.
	 */
	private function sanitize_ads( $ads ): array {
		$default_props = [
			'int'              => 0,
			'type'             => '',
			'placement_id'     => '',
			'tracking_enabled' => false,
			'output'           => '',
		];

		$out = [];

		foreach ( $ads as $ad ) {
			$ad = wp_parse_args( $ad, $default_props );
			if ( 'ad' === $ad['type'] && ! empty( trim( $ad['output'] ) ) && $ad['tracking_enabled'] ) {
				$out[] = $ad;
			}
		}

		return $out;
	}

	/**
	 * Check if this placement holds a delayed ad.
	 *
	 * @param array $placement Options array for placement.
	 *
	 * @return bool whether this is a delayed placement.
	 */
	private function is_delayed_placement( $placement ): bool {
		$is_layer_placement  = isset( $placement['layer_placement'] ) && ! empty( $placement['layer_placement']['trigger'] );
		$is_sticky_placement = isset( $placement['sticky'] ) && ! empty( $placement['sticky']['trigger'] );

		return $is_layer_placement || $is_sticky_placement;
	}

	/**
	 * Get all ads that belong to a certain group.
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array The Ad ids for this group.
	 */
	private function get_ads_in_group( $group_id ): array {
		return wp_advads_ad_query(
			[
				'fields'    => 'ids',
				'tax_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => Constants::TAXONOMY_GROUP,
						'field'    => 'term_id',
						'terms'    => $group_id,
					],
				],
			]
		)->posts;
	}
}
