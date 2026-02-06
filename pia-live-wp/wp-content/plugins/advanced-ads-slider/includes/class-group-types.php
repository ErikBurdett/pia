<?php
/**
 * Group Types.
 *
 * @package AdvancedAds\Slider
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   1.5.0
 */

namespace AdvancedAds\Slider;

use AdvancedAds\Framework\Interfaces\Integration_Interface;
use AdvancedAds\Groups\Group_Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Group Types.
 */
class Group_Types implements Integration_Interface {

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'advanced-ads-group-types', [ $this, 'add_group_type' ] );
	}

	/**
	 * Add slider group type
	 *
	 * @param array $group_types Existing group types.
	 *
	 * @return array $group_types group types with the new slider group
	 */
	public function add_group_type( array $group_types ) {
		$group_types['slider'] = [
			'title'       => __( 'Ad Slider', 'slider-ads' ),
			'description' => __( 'Display all ads as a slider', 'slider-ads' ),
			'is_premium'  => false,
			'image'       => ADVADS_BASE_URL . 'admin/assets/img/groups/slider.svg',
			'classname'   => Group_Slider::class,
		];

		return $group_types;
	}
}
