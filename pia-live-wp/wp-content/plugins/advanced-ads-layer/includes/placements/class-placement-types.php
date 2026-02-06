<?php
/**
 * Placements type manager.
 *
 * @package AdvancedAds\Layer
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.8.0
 */

namespace AdvancedAds\Layer\Placements;

use AdvancedAds\Layer\Placements\Types\Layer;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Placement Types.
 */
class Placement_Types implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-placement-types-manager', [ $this, 'add_placements' ] );
	}

	/**
	 * Add pro placement to list of placements
	 *
	 * @since 2.26.0
	 *
	 * @param Types $manager Placement types manager.
	 *
	 * @return void
	 */
	public function add_placements( $manager ) {
		$manager->register_type( Layer::class );
	}
}
