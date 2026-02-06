<?php
/**
 * Placements Types Bbpress Static.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Placements\Types;

use AdvancedAds\Interfaces\Placement_Type;
use AdvancedAds\Abstracts\Placement_Type as Base;
use AdvancedAds\Pro\Placements\Placement_Bbpress_Static;

defined( 'ABSPATH' ) || exit;

/**
 * Bbpress Static.
 */
class Bbpress_Static extends Base implements Placement_Type {

	/**
	 * Get the unique identifier (ID) of the placement type.
	 *
	 * @return string The unique ID of the placement type.
	 */
	public function get_id(): string {
		return 'bbPress static';
	}

	/**
	 * Get the class name of the object as a string.
	 *
	 * @return string
	 */
	public function get_classname(): string {
		return Placement_Bbpress_Static::class;
	}

	/**
	 * Get the title or name of the placement type.
	 *
	 * @return string The title of the placement type.
	 */
	public function get_title(): string {
		return __( 'bbPress Static Content', 'advanced-ads-pro' );
	}

	/**
	 * Get a description of the placement type.
	 *
	 * @return string The description of the placement type.
	 */
	public function get_description(): string {
		return __( 'Display ads on bbPress related pages.', 'advanced-ads-pro' );
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
		return AA_PRO_BASE_URL . 'modules/bbpress/assets/img/bbpress-static.png';
	}

	/**
	 * Get order number for this placement type.
	 *
	 * @return int The order number.
	 */
	public function get_order(): int {
		return 32;
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
