<?php
/**
 * Placement Types.
 *
 * @package AdvancedAds\StickyAds
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.9.0
 */

namespace AdvancedAds\StickyAds;

use AdvancedAds\StickyAds\Types;
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
		add_action( 'advanced-ads-placement-types-manager', [ $this, 'add_sticky_placement' ] );
	}

	/**
	 * Add sticky placement to list of placements
	 *
	 * @since 1.2.3
	 *
	 * @param Types $manager Placement types manager.
	 *
	 * @return void
	 */
	public function add_sticky_placement( $manager ) {
		$manager->register_type( Types\Sticky_Header::class );
		$manager->register_type( Types\Sticky_Footer::class );
		$manager->register_type( Types\Sticky_Left_Sidebar::class );
		$manager->register_type( Types\Sticky_Right_Sidebar::class );
		$manager->register_type( Types\Sticky_Left_Window::class );
		$manager->register_type( Types\Sticky_Right_Window::class );
	}
}
