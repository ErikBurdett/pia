<?php
/**
 * Placements Types Server.
 *
 * @package AdvancedAds\Pro
 * @author  Advanced Ads <info@wpadvancedads.com>
 * @since   2.26.0
 */

namespace AdvancedAds\Pro\Placements\Types;

defined( 'ABSPATH' ) || exit;

use AdvancedAds\Interfaces\Placement_Type;
use AdvancedAds\Pro\Placements\Placement_Server;
use AdvancedAds\Abstracts\Placement_Type as Base;

/**
 * Placements Types Server.
 */
class Server extends Base implements Placement_Type {

	/**
	 * Get the unique identifier (ID) of the placement type.
	 *
	 * @return string The unique ID of the placement type.
	 */
	public function get_id(): string {
		return 'server';
	}

	/**
	 * Get the class name of the object as a string.
	 *
	 * @return string
	 */
	public function get_classname(): string {
		return Placement_Server::class;
	}

	/**
	 * Get the title or name of the placement type.
	 *
	 * @return string The title of the placement type.
	 */
	public function get_title(): string {
		return __( 'Ad Server', 'advanced-ads-pro' );
	}

	/**
	 * Get a description of the placement type.
	 *
	 * @return string The description of the placement type.
	 */
	public function get_description(): string {
		return __( 'Display ads on external websites.', 'advanced-ads-pro' );
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
		return AA_PRO_BASE_URL . 'modules/ad-server/assets/img/server.png';
	}

	/**
	 * Get order number for this placement type.
	 *
	 * @return int The order number.
	 */
	public function get_order(): int {
		return 110;
	}

	/**
	 * Get options for this placement type.
	 *
	 * @return array The options array.
	 */
	public function get_options(): array {
		return $this->apply_filter_on_options(
			[
				'placement-cache-busting'      => false,
				'placement-display-conditions' => false,
				'placement-visitor-conditions' => false,
				'placement-item-alternative'   => false,
				'placement-tests'              => false,
			]
		);
	}
}
