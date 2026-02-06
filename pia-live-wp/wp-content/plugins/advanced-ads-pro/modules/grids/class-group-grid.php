<?php
/**
 * This class is responsible to model grid groups.
 *
 * @package AdvancedAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.48.0
 */

namespace AdvancedAds\Pro\Modules\Grids;

use AdvancedAds\Abstracts\Group;
use AdvancedAds\Interfaces\Group_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Grid group.
 */
class Group_Grid extends Group implements Group_Interface {

	/**
	 * Get columns.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_columns( $context = 'view' ): int {
		return $this->get_prop( 'columns', $context );
	}

	/**
	 * Get rows.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_rows( $context = 'view' ): int {
		return $this->get_prop( 'rows', $context );
	}

	/**
	 * Get inner margin.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_inner_margin( $context = 'view' ): int {
		return $this->get_prop( 'inner_margin', $context );
	}

	/**
	 * Get min width.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_min_width( $context = 'view' ): int {
		return $this->get_prop( 'min_width', $context );
	}

	/**
	 * Get full width breakpoint.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_full_width_breakpoint( $context = 'view' ): int {
		return $this->get_prop( 'full_width_breakpoint', $context );
	}

	/**
	 * Is grid display random.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 */
	public function is_random( $context = 'view' ): bool {
		return $this->get_prop( 'random', $context );
	}
}
