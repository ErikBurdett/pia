<?php // phpcs:ignoreFilename
/**
 * This class merge a placement display and visitors conditions into its child ad's conditions.
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 */

use AdvancedAds\Abstracts\Ad;
use AdvancedAds\Abstracts\Placement;
use AdvancedAds\Framework\Utilities\Params;

/**
 * Class Placement Conditions
 */
class Advanced_Ads_Pro_Module_Placement_Conditions {
	/**
	 * The constructor.
	 */
	public function __construct() {
		add_filter( 'advanced-ads-placements-updated', [ $this, 'maybe_refresh_placement_list' ], 10, 2 );
		add_filter( 'advanced-ads-ad-get-conditions', [ $this, 'add_display_conditions_to_ad' ], 10, 2 );
		add_filter( 'advanced-ads-ad-get-visitors', [ $this, 'add_visitor_conditions_to_ad' ], 10, 2 );
	}

	/**
	 * Add placement visitor conditions to ads
	 *
	 * @param array $value ad visitor conditions.
	 * @param Ad $ad the ad.
	 *
	 * @return array
	 */
	public function add_visitor_conditions_to_ad( $value, $ad ) {
		return $this->append_conditions( 'visitors', $value, $ad );
	}

	/**
	 * Add display conditions to ads
	 *
	 * @param array $value ad visitor conditions.
	 * @param Ad $ad the ad.
	 *
	 * @return array
	 */
	public function add_display_conditions_to_ad( $value, $ad ) {
		return $this->append_conditions( 'display', $value, $ad );
	}

	/**
	 * Reload the placement list page if visitor or display conditions have changed to update the WP_List_Table instance.
	 *
	 * @param array     $update_data data related to the update process.
	 * @param Placement $placement   current placement.
	 *
	 * @return array
	 */
	public function maybe_refresh_placement_list( $update_data, $placement ) {
		$options            = $update_data['payload']['advads']['placements']['options'] ?? [];
		$payload_conditions = [
			'display' => $options['display'] ?? [],
			'visitor' => $options['visitor'] ?? [],
		];
		$saved_conditions   = [
			'display' => $update_data['placement_data']['display'] ?? [],
			'visitor' => $update_data['placement_data']['visitor'] ?? [],
		];

		// If conditions differ, refresh the placement list page.
		if ( md5( wp_json_encode( $payload_conditions ) ) !== md5( wp_json_encode( $saved_conditions ) ) ) {
			if ( empty( $payload_conditions['display'] ) ) {
				$placement->set_prop( 'display', [] );
			}
			if ( empty( $payload_conditions['visitor'] ) ) {
				$placement->set_prop( 'visitor', [] );
			}
			$update_data['reload'] = true;
		}

		return $update_data;
	}

	/**
	 * Append placement condition to the ad conditions
	 *
	 * @param string $type       conditions type, 'display'|'visitors'.
	 * @param array  $conditions conditions from the ad.
	 * @param Ad     $ad         the ad we're getting the prop from.
	 *
	 * @return array
	 */
	private function append_conditions( string $type, array $conditions, Ad $ad ) {
		if ( is_admin() && 'advads_ad_select' !== Params::request( 'action' ) ) {
			return $conditions;
		}

		$placement = $ad->get_root_placement();

		if ( ! $placement ) {
			return $conditions;
		}

		$placement_conditions = $placement->get_prop( $type );

		if ( empty( $placement_conditions ) ) {
			return $conditions;
		}

		$conditions_values = array_values( $placement_conditions );

		if ( ! isset( $conditions_values[0] ) ) {
			$conditions_values[0] = [];
		}

		// Append placement conditions to the ad conditions using the 'AND' connector.
		$conditions_values[0]['connector'] = 'and';

		return array_merge( $conditions, $conditions_values );
	}
}
