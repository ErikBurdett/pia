<?php
/**
 * Placements type manager
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Placements;

use Advanced_Ads_Pro;
use AdvancedAds\Pro\Placements\Types;
use AdvancedAds\Framework\Interfaces\Integration_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Placements Placement Types.
 */
class Placement_Types implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'advanced-ads-placement-types-manager', [ $this, 'add_pro_placements' ] );
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
	public function add_pro_placements( $manager ) {
		$manager->register_type( Types\Above_Headline::class );
		$manager->register_type( Types\Archive_Pages::class );
		$manager->register_type( Types\Content_Middle::class );
		$manager->register_type( Types\Content_Random::class );
		$manager->register_type( Types\Custom_Position::class );
		$manager->register_type( Types\Background_Ad::class );

		$options = Advanced_Ads_Pro::get_instance()->get_options();
		if ( ! empty( $options['ad-server']['enabled'] ) ) {
			$manager->register_type( Types\Server::class );
		}

		if ( class_exists( 'bbPress', false ) ) {
			$manager->register_type( Types\Bbpress_Static::class );
			$manager->register_type( Types\Bbpress_Comment::class );
		}

		if ( class_exists( 'BuddyPress', false ) ) {
			$manager->register_type( Types\Buddypress::class );
		}
	}
}
