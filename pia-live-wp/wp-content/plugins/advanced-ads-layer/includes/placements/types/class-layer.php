<?php
/**
 * This class represents the "Popup & Layer" placement type.
 *
 * @package AdvancedAds\Layer
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.8.0
 */

namespace AdvancedAds\Layer\Placements\Types;

use AdvancedAds\Interfaces\Placement_Type;
use AdvancedAds\Abstracts\Placement_Type as Base;
use AdvancedAds\Layer\Placements\Placement_Layer;

defined( 'ABSPATH' ) || exit;

/**
 * Layer & Popup.
 */
class Layer extends Base implements Placement_Type {

	/**
	 * Get the unique identifier (ID) of the placement type.
	 *
	 * @return string The unique ID of the placement type.
	 */
	public function get_id(): string {
		return 'layer';
	}

	/**
	 * Get the class name of the object as a string.
	 *
	 * @return string
	 */
	public function get_classname(): string {
		return Placement_Layer::class;
	}

	/**
	 * Get the title or name of the placement type.
	 *
	 * @return string The title of the placement type.
	 */
	public function get_title(): string {
		return __( 'PopUp and Layer Ads', 'advanced-ads-layer' );
	}

	/**
	 * Get a description of the placement type.
	 *
	 * @return string The description of the placement type.
	 */
	public function get_description(): string {
		return __( 'Create PopUp or Layer Ad', 'advanced-ads-layer' );
	}

	/**
	 * Check if this placement type requires premium.
	 *
	 * @return bool True if premium is required; otherwise, false.
	 */
	public function is_premium(): bool {
		return false;
	}

	/**
	 * Get the URL for upgrading to this placement type.
	 *
	 * @return string The upgrade URL for the placement type.
	 */
	public function get_image(): string {
		return AA_LAYER_ADS_BASE_URL . 'admin/assets/img/layer.png';
	}

	/**
	 * Get order number for this placement type.
	 *
	 * @return int The order number.
	 */
	public function get_order(): int {
		return 75;
	}

	/**
	 * Get options for this placement type.
	 *
	 * @return array The options array.
	 */
	public function get_options(): array {
		return $this->apply_filter_on_options( [] );
	}
}
