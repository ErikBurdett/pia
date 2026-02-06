<?php
/**
 * Modules Grids Type Grid.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Modules\Grids;

use AdvancedAds\Groups\Types\Grid as TypeGrid;

defined( 'ABSPATH' ) || exit;

/**
 * Type Grid.
 */
class Type_Grid extends TypeGrid {

	/**
	 * Get the class name of the object as a string.
	 *
	 * @return string
	 */
	public function get_classname(): string {
		return Group_Grid::class;
	}

	/**
	 * Check if this group type requires premium.
	 *
	 * @return bool True if premium is required; otherwise, false.
	 */
	public function is_premium(): bool {
		return false;
	}
}
