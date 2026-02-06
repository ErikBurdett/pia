<?php
/**
 * This class represents the "Sticky Right Sidebar" placement type.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds\Types;

use AdvancedAds\Abstracts\Placement_Type as Base;
use AdvancedAds\Interfaces\Placement_Type;
use AdvancedAds\Placements\Placement_Standard;

defined( 'ABSPATH' ) || exit;

/**
 * Type Sticky Right Sidebar.
 */
class Sticky_Right_Sidebar extends Base implements Placement_Type {

	/**
	 * Get the unique identifier (ID) of the placement type.
	 *
	 * @return string The unique ID of the placement type.
	 */
	public function get_id(): string {
		return 'sticky_right_sidebar';
	}

	/**
	 * Get the class name of the object as a string.
	 *
	 * @return string
	 */
	public function get_classname(): string {
		return Placement_Standard::class;
	}

	/**
	 * Get the title or name of the placement type.
	 *
	 * @return string The title of the placement type.
	 */
	public function get_title(): string {
		return __( 'Right Sidebar', 'advanced-ads-sticky' );
	}

	/**
	 * Get a description of the placement type.
	 *
	 * @return string The description of the placement type.
	 */
	public function get_description(): string {
		return __( 'Sidebar on the right side of the content wrapper.', 'advanced-ads-sticky' );
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
		return AA_STICKY_ADS_BASE_URL . 'assets/img/sticky-sidebar-right.png';
	}

	/**
	 * Get order number for this placement type.
	 *
	 * @return int The order number.
	 */
	public function get_order(): int {
		return 60;
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
